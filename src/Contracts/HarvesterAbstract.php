<?php

namespace Imamuseum\Harvester\Contracts;

use Imamuseum\Harvester\Models\Source;
use Imamuseum\Harvester\Models\Actor;

abstract class HarvesterAbstract {

    public function createTypes()
    {
        // get config array for harvester types
        $configTypes = config('harvester.types');
        // loop through types and insert
        foreach ($configTypes as $keyType => $valueType) {
            foreach($valueType as $type) {
                $typeModel = $keyType . "_types";
                $typeName = $keyType . "_type_name";
                $typeDesc = $keyType . "_type_desc";
                \DB::table($typeModel)->insert([
                    $typeName => $type['name'],
                    $typeDesc =>  $type['desc']
                ]);
            }
        }
    }

    public function createOrUpdateAssetSource($object_id, $images)
    {
        $sequence = 0;
        foreach ($images as $image) {
            $asset = Source::firstOrNew(['origin_id' => $image->source_id]);
            $asset->object_id = $object_id;
            $asset->source_uri = $image->source_url;
            $asset->source_sequence = $sequence;
            $asset->save();
            $sequence++;
        }
    }

    public function createOrUpdateActors($actors)
    {
        $actorSync = null;
        if ($actors != null) {
            $sequence = 0;
            foreach ($actors as $actorData) {
                $actor = Actor::firstOrNew(['actor_uid' => $actorData->actor_uid]);
                $actor->actor_uid = $actorData->actor_uid;
                $actor->actor_name_display = $actorData->actor_name_display;
                $actor->actor_name_first = isset($actorData->name_first) ? $actorData->name_first : null;
                $actor->actor_name_last = isset($actorData->name_last) ? $actorData->name_last : null;
                $actor->actor_name_middle = isset($actorData->name_middle) ? $actorData->name_middle : null;
                $actor->actor_name_suffix = isset($actorData->name_suffix) ? $actorData->name_suffix : null;
                $actor->birth_date = isset($actorData->birth_date) ? $actorData->birth_date : null;
                $actor->birth_location = isset($actorData->birth_location) ? $actorData->birth_location : null;
                $actor->work_location = isset($actorData->work_location) ? $actorData->work_location : null;
                $actor->death_date = isset($actorData->death_date) ? $actorData->death_date : null;
                $actor->death_location = isset($actorData->death_location) ? $actorData->death_location : null;
                $actor->actor_nationality = isset($actorData->actor_nationality) ? $actorData->actor_nationality : null;
                $actor->actor_custom = isset($actorData->actor_custom) ? $actorData->actor_custom : null;
                $actor->save();

                $actorSync[$actor->id] = ['role' => $actorData->role, 'sequence' => $sequence];

                if (isset($actorData->dates)) {
                    $actorDateIDs = $this->createOrFindDates($actorData->dates);
                    $actor->dates()->sync($actorDateIDs);
                }

                if (isset($actorData->locations)) {
                    $actorLocationIDs = $this->createOrFindLocations($actorData->locations);
                    $actor->locations()->sync($actorlocationIDs);
                }

                $sequence++;
            }
            return $actorSync;
        }
    }

    public function createOrFindTerms($fields)
    {
        $field_ids = null;
        foreach($fields as $type => $contents) {
            $type_id = \DB::table('term_types')->where('term_type_name', '=', $type)->pluck('id');
            foreach ($contents as $content) {
                if ($content != '') {
                    $content_id = \DB::table('terms')->where('term_type_id', '=', $type_id[0])->where('term', '=', $content)->pluck('id');
                    // in no content insert new content
                    if (empty($content_id)) {
                        $new_content_id = \DB::table('terms')->insertGetId([
                            'term' => $content,
                            'term_type_id' =>  $type_id[0]
                        ]);
                        // append new id to field ID array
                        $field_ids[] = $new_content_id;
                    }

                    // if content already exists add field ID to array
                    if (! empty($content_id)) {
                        $field_ids[] = $content_id[0];
                    }
                }
            }
        }
        return $field_ids;
    }

    public function createOrFindDates($fields)
    {
        $field_ids = null;
        $fields = is_object($fields) ? json_decode(json_encode($fields), true) : $fields;
        foreach($fields as $type => $contents) {
            $type_id = \DB::table('date_types')->where('date_type_name', '=', $type)->pluck('id');

            if ($contents['date'] != '') {
                $content_id = \DB::table('dates')->where('date_type_id', '=', $type_id[0])->where('date', '=', $contents['date'])->pluck('id');

                if (empty($content_id)) {
                    $new_content_id = \DB::table('dates')->insertGetId([
                        'date' => $contents['date'],
                        'date_at' => isset($contents['date_at']) ? $contents['date_at'] : null,
                        'date_type_id' =>  $type_id[0]
                    ]);
                    // append new id to field ID array
                    $field_ids[] = $new_content_id;
                }

                // if content already exists add field ID to array
                if (! empty($content_id)) {
                    $field_ids[] = $content_id[0];
                }
            }
        }
        return $field_ids;
    }

    public function createOrFindLocations($fields)
    {
        $field_ids = null;
        $fields = is_object($fields) ? json_decode(json_encode($fields), true) : $fields;
        foreach($fields as $type => $contents) {
            $type_id = \DB::table('location_types')->where('location_type_name', '=', $type)->pluck('id');
            if ($contents['location'] != '') {
                $content_id = \DB::table('locations')->where('location_type_id', '=', $type_id[0])->where('location', '=', $contents['location'])->pluck('id');

                if (empty($content_id)) {
                    $new_content_id = \DB::table('locations')->insertGetId([
                        'location' => $contents['location'],
                        'latitude' => isset($contents['latitude']) ? $contents['latitude'] : null,
                        'longitude' => isset($contents['longitude']) ? $contents['longitude'] : null,
                        'location_type_id' =>  $type_id[0]
                    ]);
                    // append new id to field ID array
                    $field_ids[] = $new_content_id;
                }

                // if content already exists add field ID to array
                if (! empty($content_id)) {
                    $field_ids[] = $content_id[0];
                }
            }
        }
        return $field_ids;
    }

    public function createOrUpdateTexts($object_id, $texts)
    {
        foreach ($texts as $key => $value) {
            if ($value != '') {
                $text_type_id = \DB::table('text_types')->where('text_type_name', '=', $key)->pluck('id');
                $text = \Imamuseum\Harvester\Models\Text::where('text_type_id', '=', $text_type_id[0])->where('object_id', '=', $object_id)->first();
                if ($text) {
                    $text->text = $value;
                }

                if (!$text) {
                    $text = new \Imamuseum\Harvester\Models\Text();
                    $text->text = $value;
                    $text->object_id = $object_id;
                    $text->text_type_id = $text_type_id[0];
                }

                $text->save();
            }
        }
    }

}
