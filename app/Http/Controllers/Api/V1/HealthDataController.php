<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\DataSource;
use App\Models\HealthIndicatorValue;
use App\Models\Indicator;
use App\Services\DataQuality\DataQualityService;
use App\Support\ApprovalWorkflow;
use App\Support\UserCountryAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class HealthDataController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'name' => config('app.name'),
            'version' => 'v1',
            'authenticated' => true,
            'scope' => UserCountryAccess::canViewRegionalDashboard() ? 'african_region' : 'country',
            'country_location_id' => UserCountryAccess::locationId(),
            'user' => [
                'id' => $request->user()?->id,
                'name' => $request->user()?->name,
                'email' => $request->user()?->email,
            ],
        ]);
    }

    public function indicatorValueSchema(): JsonResponse
    {
        $fields = $this->tableColumnNames(HealthIndicatorValue::class);

        return response()->json([
            'resource' => 'indicator-values',
            'primary_key' => 'fact_id',
            'fields' => $fields,
            'required_for_post' => $this->requiredForPostFields(),
            'writable_fields' => $this->writableFields(),
            'filters' => [
                'indicator_id',
                'location_id',
                'period',
                'datasource_id',
                'per_page',
            ],
        ]);
    }

    public function locations(Request $request): JsonResponse
    {
        $query = Country::query()
            ->with('translations')
            ->where('locationlevel_id', 2)
            ->orderBy('code');

        UserCountryAccess::scopeDashboard($query, 'location_id');

        return response()->json(
            $query
                ->limit($this->limit($request, 250))
                ->get()
                ->map(fn (Country $country): array => [
                    'location_id' => $country->location_id,
                    'code' => $country->code,
                    'iso_alpha' => $country->iso_alpha,
                    'name' => $country->display_name,
                ])
                ->values(),
        );
    }

    public function indicators(Request $request): JsonResponse
    {
        $query = Indicator::query()
            ->with('translations')
            ->orderBy('afrocode');

        return response()->json(
            $query
                ->limit($this->limit($request, 250))
                ->get()
                ->map(fn (Indicator $indicator): array => [
                    'indicator_id' => $indicator->indicator_id,
                    'afrocode' => $indicator->afrocode,
                    'gen_code' => $indicator->gen_code,
                    'name' => $indicator->display_name,
                ])
                ->values(),
        );
    }

    public function dataSources(Request $request): JsonResponse
    {
        $query = DataSource::query()
            ->with('translations')
            ->orderBy('code');

        return response()->json(
            $query
                ->limit($this->limit($request, 250))
                ->get()
                ->map(fn (DataSource $source): array => [
                    'datasource_id' => $source->datasource_id,
                    'code' => $source->code,
                    'name' => $source->display_name,
                ])
                ->values(),
        );
    }

    public function indicatorValues(Request $request): JsonResponse
    {
        $query = $this->indicatorValueQuery(approvedOnly: true)
            ->latest('fact_id');

        foreach (['indicator_id', 'location_id', 'period', 'datasource_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $values = $query
            ->paginate($this->limit($request, 100))
            ->through(fn (HealthIndicatorValue $value): array => $this->serializeIndicatorValue($value));

        return response()->json($values);
    }

    public function showIndicatorValue(int|string $record): JsonResponse
    {
        $value = $this->findIndicatorValue($record, approvedOnly: true);

        if (! $value) {
            return $this->notFound();
        }

        return response()->json($this->serializeIndicatorValue($value));
    }

    public function storeIndicatorValue(Request $request): JsonResponse
    {
        $data = $this->validateIndicatorValue($request, creating: true);
        $data = $this->forcePendingApproval($data);

        if ($response = $this->authorizeLocation($data['location_id'])) {
            return $response;
        }

        $this->validateQuality($data);

        $value = HealthIndicatorValue::create($data);

        return response()->json($this->serializeIndicatorValue($value), 201);
    }

    public function updateIndicatorValue(Request $request, int|string $record): JsonResponse
    {
        $value = $this->findIndicatorValue($record);

        if (! $value) {
            return $this->notFound();
        }

        $data = $this->validateIndicatorValue($request, creating: false);

        if (array_key_exists('location_id', $data)) {
            $response = $this->authorizeLocation($data['location_id']);

            if ($response) {
                return $response;
            }
        }

        $this->validateQuality([...Arr::only($value->getAttributes(), $this->writableFields()), ...$data]);

        $value->fill($data);
        $value->save();

        return response()->json($this->serializeIndicatorValue($value));
    }

    public function destroyIndicatorValue(int|string $record): JsonResponse
    {
        $value = $this->findIndicatorValue($record);

        if (! $value) {
            return $this->notFound();
        }

        $value->delete();

        return response()->json([
            'message' => 'Indicator value deleted.',
            'fact_id' => (int) $record,
        ]);
    }

    private function indicatorValueQuery(bool $approvedOnly = false): Builder
    {
        $query = HealthIndicatorValue::query();

        UserCountryAccess::scopeDashboard($query);

        if ($approvedOnly) {
            ApprovalWorkflow::whereApproved($query);
        }

        return $query;
    }

    private function findIndicatorValue(int|string $record, bool $approvedOnly = false): ?HealthIndicatorValue
    {
        return $this->indicatorValueQuery(approvedOnly: $approvedOnly)
            ->whereKey($record)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateIndicatorValue(Request $request, bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'uuid' => ['sometimes', 'nullable', 'string', 'max:36'],
            'indicator_id' => [$required, 'integer'],
            'location_id' => [$required, 'integer'],
            'start_period' => [$required, 'integer'],
            'end_period' => [$required, 'integer'],
            'period' => [$required, 'string', 'max:25'],
            'categoryoption_id' => [$required, 'integer'],
            'datasource_id' => [$required, 'integer'],
            'measuremethod_id' => [$required, 'integer'],
            'value_received' => [$required, 'numeric'],
            'numerator_value' => ['sometimes', 'nullable', 'numeric'],
            'denominator_value' => ['sometimes', 'nullable', 'numeric'],
            'min_value' => ['sometimes', 'nullable', 'numeric'],
            'max_value' => ['sometimes', 'nullable', 'numeric'],
            'target_value' => ['sometimes', 'nullable', 'numeric'],
            'string_value' => ['sometimes', 'nullable', 'string', 'max:500'],
            'comment' => ['sometimes', 'nullable', Rule::in([
                ApprovalWorkflow::STATUS_PENDING,
                ApprovalWorkflow::STATUS_APPROVED,
                ApprovalWorkflow::STATUS_REJECTED,
            ])],
            'priority' => ['sometimes', 'boolean'],
            'user_id' => ['sometimes', 'integer'],
            'approval_status' => ['sometimes', 'nullable', Rule::in([
                ApprovalWorkflow::STATUS_PENDING,
                ApprovalWorkflow::STATUS_APPROVED,
                ApprovalWorkflow::STATUS_REJECTED,
            ])],
            'approved_by' => ['sometimes', 'nullable', 'integer'],
            'approved_at' => ['sometimes', 'nullable', 'date'],
        ]);
    }

    private function validateQuality(array $data): void
    {
        $issues = app(DataQualityService::class)->inspectIndicatorPayload($data);

        if ($issues === []) {
            return;
        }

        throw ValidationException::withMessages([
            'value_received' => collect($issues)->pluck('message')->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function forcePendingApproval(array $data): array
    {
        $data[ApprovalWorkflow::STATUS_COLUMN] = ApprovalWorkflow::STATUS_PENDING;
        $data[ApprovalWorkflow::MIRROR_COLUMN] = ApprovalWorkflow::STATUS_PENDING;
        $data['approved_by'] = null;
        $data['approved_at'] = null;

        return $data;
    }

    private function authorizeLocation(int|string $locationId): ?JsonResponse
    {
        if (UserCountryAccess::canViewRegionalDashboard()) {
            return null;
        }

        if (in_array((int) $locationId, UserCountryAccess::allowedLocationIds(), true)) {
            return null;
        }

        return response()->json([
            'message' => 'This API token cannot write data for this country.',
        ], 403);
    }

    /**
     * @return array<int, string>
     */
    private function writableFields(): array
    {
        return array_values(array_diff(
            $this->tableColumnNames(HealthIndicatorValue::class),
            ['fact_id', 'date_created', 'date_lastupdated'],
        ));
    }

    /**
     * @return array<int, string>
     */
    private function requiredForPostFields(): array
    {
        return [
            'indicator_id',
            'location_id',
            'start_period',
            'end_period',
            'period',
            'categoryoption_id',
            'datasource_id',
            'measuremethod_id',
            'value_received',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeIndicatorValue(HealthIndicatorValue $value): array
    {
        return collect($this->tableColumnNames(HealthIndicatorValue::class))
            ->mapWithKeys(fn (string $field): array => [$field => $this->serializeFieldValue($value->getAttribute($field))])
            ->all();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<int, string>
     */
    private function tableColumnNames(string $modelClass): array
    {
        /** @var Model $model */
        $model = new $modelClass;

        try {
            return DatabaseSchema::connection($model->getConnectionName())->getColumnListing($model->getTable());
        } catch (Throwable) {
            return [
                'fact_id',
                'uuid',
                'numerator_value',
                'denominator_value',
                'value_received',
                'min_value',
                'max_value',
                'target_value',
                'string_value',
                'start_period',
                'end_period',
                'period',
                'comment',
                'date_created',
                'date_lastupdated',
                'categoryoption_id',
                'datasource_id',
                'indicator_id',
                'location_id',
                'measuremethod_id',
                'user_id',
                'priority',
                'approval_status',
                'approved_by',
                'approved_at',
            ];
        }
    }

    private function serializeFieldValue(mixed $value): mixed
    {
        if ($value instanceof Carbon) {
            return $value->toISOString();
        }

        return $value;
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'Indicator value not found.',
        ], 404);
    }

    private function limit(Request $request, int $default): int
    {
        return min(max((int) $request->integer('per_page', $default), 1), 500);
    }
}
