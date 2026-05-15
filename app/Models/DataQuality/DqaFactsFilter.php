<?php

namespace App\Models\DataQuality;

class DqaFactsFilter extends DqaLookupModel
{
    protected $table = 'dqa_filter_facts_dataframe';

    protected $primaryKey = 'filter_id';
}
