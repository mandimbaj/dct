<?php

namespace Tests\Unit;

use App\Models\Country;
use App\Models\NationalObservatory;
use App\Models\NationalObservatoryTranslation;
use App\Models\User;
use App\Notifications\MessageReceived;
use App\Support\NationalObservatoryNotifier;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NationalObservatoryNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_actor_are_notified_when_national_observatory_changes(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $actor = User::factory()->create([
            'is_super_admin' => false,
            'location_id' => 7,
        ]);

        $this->actingAs($actor);

        NationalObservatoryNotifier::record(
            NationalObservatoryNotifier::ACTION_CREATED,
            $this->observatory(),
        );

        Notification::assertSentTo($admin, MessageReceived::class);
        Notification::assertSentTo($actor, MessageReceived::class);
    }

    private function observatory(): NationalObservatory
    {
        $country = new Country;
        $country->forceFill([
            'location_id' => 7,
            'iso_alpha' => 'BI',
            'code' => 'BI108',
        ]);

        $translation = new NationalObservatoryTranslation;
        $translation->forceFill([
            'language_code' => 'en',
            'name' => 'National Health Observatory in Burundi',
        ]);

        $observatory = new NationalObservatory;
        $observatory->forceFill([
            'observatory_id' => 5,
            'code' => 'NHO-BI',
            'location_id' => 7,
            'user_id' => 12,
        ]);
        $observatory->setRelation('location', $country);
        $observatory->setRelation('translations', new EloquentCollection([$translation]));

        return $observatory;
    }
}
