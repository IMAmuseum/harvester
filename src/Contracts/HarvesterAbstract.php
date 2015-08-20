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
        $fieldIDs = null;
        foreach($fields as $type => $contents) {
            //get type id for content
            $typeID = \DB::table($model.'_types')->where($model.'_type_name', '=', $type)->pluck('id');
            foreach ($contents as $key => $content) {
                // if content is not a blank string
                if ($content != '') {
                    // make all fields lowercase
                    $content = strtolower($content);
                    // check if contentID already exists
                    $contentID = \DB::table(str_plural($model))->where($model.'_type_id', '=', $typeID)->where($model, '=', $content)->pluck('id');

                    // in no content insert new content
                    if (is_null($contentID)) {
                        \DB::table(str_plural($model))->insert([
                            $model => $content,
                            $model.'_type_id' =>  $typeID
                        ]);
                        // retrieve newly created content ID
                        $newContentID = \DB::table(str_plural($model))->where($model.'_type_id', '=', $typeID)->where($model, '=', $content)->pluck('id');
                        // append new id to field ID array
                        $fieldIDs[] = $newContentID;
                    }

                    // if content already exists add field ID to array
                    if (! is_null($contentID)) {
                        $fieldIDs[] = $contentID;
                    }
                }
            }
        }
        return $fieldIDs;
    }

    public function createOrUpdateTexts($objectID, $texts)
    {
        foreach ($texts as $key => $value) {
            if ($value != '') {
                $textTypeID = \DB::table('text_types')->where('text_type_name', '=', $key)->pluck('id');
                $text = \Imamuseum\Harvester\Models\Text::where('text_type_id', '=', $textTypeID)->where('object_id', '=', $objectID)->first();

                if ($text) {
                    $text->text = $value;
                }

                if (!$text) {
                    $text = new \App\Models\Text();
                    $text->text = $value;
                    $text->object_id = $objectID;
                    $text->text_type_id = $textTypeID;
                }

                $text->save();
            }
        }
    }

    public function createAssets($objectID, $images)
    {
        $sequence = 1;
        foreach ($images as $image) {
            $asset = new \Imamuseum\Harvester\Models\Asset();
            $asset->asset_type_id = \DB::table('asset_types')->where('asset_type_name', '=', 'piction')->pluck('id');
            $asset->object_id = $objectID;
            $asset->asset_sequence = $sequence;
            $asset->asset_file_uri = $image->source_url;
            $asset->save();
            $sequence++;
        }
    }

    public function createOrUpdateActors($actors)
    {
        $actorSync = null;
        if ($actors != null) {
            $sequence = 1;
            foreach ($actors as $actorData) {
                $actor = \Imamuseum\Harvester\Models\Actor::firstOrNew(['actor_uid' => $actorData['name']]);
                $actor->actor_uid = $actorData['name'];
                $actor->actor_name_display = $actorData['name'];
                $actor->actor_name_first = isset($actorData['name_first']) ? $actorData['name_first'] : null;
                $actor->actor_name_last = isset($actorData['name_last']) ? $actorData['name_last'] : null;
                $actor->actor_name_middle = isset($actorData['name_middle']) ? $actorData['name_middle'] : null;
                $actor->actor_name_suffix = isset($actorData['name_suffix']) ? $actorData['name_suffix'] : null;
                $actor->save();

                $actorSync[$actor->id] = ['role' => $actorData['role'], 'sequence' => $sequence];
                $sequence++;
            }
            return $actorSync;
        }
    }

}