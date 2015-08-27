<?php

namespace Imamuseum\Harvester\Contracts;


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

    // return an array of ids
    public function createOrFindFields($model, $fields)
    {
        $field_ids = null;
        foreach($fields as $type => $contents) {
            //get type id for content
            $type_id = \DB::table($model.'_types')->where($model.'_type_name', '=', $type)->pluck('id');
            foreach ($contents as $key => $content) {
                // if content is not a blank string
                if ($content != '') {
                    // make all fields lowercase
                    $content = strtolower($content);
                    // check if content_id already exists
                    $content_id = \DB::table(str_plural($model))->where($model.'_type_id', '=', $type_id)->where($model, '=', $content)->pluck('id');

                    // in no content insert new content
                    if (is_null($content_id)) {
                        \DB::table(str_plural($model))->insert([
                            $model => $content,
                            $model.'_type_id' =>  $type_id
                        ]);
                        // retrieve newly created content ID
                        $new_content_id = \DB::table(str_plural($model))->where($model.'_type_id', '=', $type_id)->where($model, '=', $content)->pluck('id');
                        // append new id to field ID array
                        $field_ids[] = $new_content_id;
                    }

                    // if content already exists add field ID to array
                    if (! is_null($content_id)) {
                        $field_ids[] = $content_id;
                    }
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
                $text = \App\Models\Text::where('text_type_id', '=', $text_type_id)->where('object_id', '=', $object_id)->first();

                if ($text) {
                    $text->text = $value;
                }

                if (!$text) {
                    $text = new \App\Models\Text();
                    $text->text = $value;
                    $text->object_id = $object_id;
                    $text->text_type_id = $text_type_id;
                }

                $text->save();
            }
        }
    }

    public function createOrUpdateAssets($asset_type_id, $object_id, $images)
    {
        $sequence = 0;
        foreach ($images as $image) {
            $asset = \App\Models\Asset::firstOrNew(['asset_file_uri' => $image->source_url]);
            $asset->asset_type_id = $asset_type_id;
            $asset->object_id = $object_id;
            $asset->asset_sequence = $sequence;
            $asset->asset_title = isset($actorData['asset_title']) ? $actorData['asset_title'] : null;
            $asset->asset_description = isset($actorData['asset_desc']) ? $actorData['asset_desc'] : null;
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
                $actor = \App\Models\Actor::firstOrNew(['actor_uid' => $actorData['name']]);
                $actor->actor_uid = $actorData['name'];
                $actor->actor_name_display = $actorData['name'];
                $actor->actor_name_first = isset($actorData['name_first']) ? $actorData['name_first'] : null;
                $actor->actor_name_last = isset($actorData['name_last']) ? $actorData['name_last'] : null;
                $actor->actor_name_middle = isset($actorData['name_middle']) ? $actorData['name_middle'] : null;
                $actor->actor_name_suffix = isset($actorData['name_suffix']) ? $actorData['name_suffix'] : null;
                $actor->birth_date = isset($actorData['birth_date']) ? $actorData['birth_date'] : null;
                $actor->birth_location = isset($actorData['birth_location']) ? $actorData['birth_location'] : null;
                $actor->work_location = isset($actorData['work_location']) ? $actorData['work_location'] : null;
                $actor->death_date = isset($actorData['death_date']) ? $actorData['death_date'] : null;
                $actor->death_location = isset($actorData['death_location']) ? $actorData['death_location'] : null;
                $actor->actor_custom = isset($actorData['actor_custom']) ? $actorData['actor_custom'] : null;
                $actor->save();

                $actorSync[$actor->id] = ['role' => $actorData['role'], 'sequence' => $sequence];
                $sequence++;
            }
            return $actorSync;
        }
    }

}