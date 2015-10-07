<?php

namespace Imamuseum\Harvester\Transformers;

class ObjectTransformer
{
    /**
     * Turn this object into a generic array
     *
     * @return array
     */
    public function transform($object)
    {
        $actors = null;
        if (! empty($object->actors)) {
            $object_actors = $object->actors;
            foreach ($object_actors as $actor_key => $actor_value) {
               $actors[] = [
                    'sequence'          => $actor_value->pivot->sequence,
                    'role'              => $actor_value->pivot->role,
                    'display_name'      => $actor_value->actor_name_display,
                    'first_name'        => $actor_value->actor_name_first,
                    'last_name'         => $actor_value->actor_name_last,
                    'middle_name'       => $actor_value->actor_name_middle,
                    'suffix'            => $actor_value->actor_name_suffix,
                    'nationality'       => $actor_value->actor_nationality,
                    'birth_date'        => $actor_value->birth_date,
                    'death_date'        => $actor_value->death_date,
                    'birth_location'    => $actor_value->birth_location,
                    'work_location'     => $actor_value->work_location,
                    'death_location'    => $actor_value->death_location,
                    'dates'             => $this->transformDates($actor_value->dates),
                    'locations'         => $this->transformDates($actor_value->locations),
                    'custom'            => $actor_value->actor_custom,
               ];
            }
        }

        $asset_group = null;
        $object_assets = $object->assets->groupBy('asset_sequence');
        foreach ($object_assets as $asset_group_key => $asset_group_value) {
            $asset_transform = null;
            foreach ($asset_group_value as $asset_item_value) {
                $asset_transform[$asset_item_value->type->asset_type_name] = [
                    'uri' => config('app.url') . $asset_item_value->asset_file_uri,
                ];
            }
            if (isset($asset_item_value->source->origin_id)) {
                $asset_group[$asset_item_value->source->origin_id] = $asset_transform;
            }
        }

        $dates = $this->transformDates($object->dates);

        $locations = $this->transformLocations($object->locations);

        $terms = null;
        if (! empty($object->terms)) {
            $object_terms = $object->terms;
            $object_terms = $object_terms->groupBy('term_type_id');
            foreach ($object_terms as $term_type) {
                $term_transform = [];
                foreach ($term_type as $value) {
                    array_push($term_transform, $value->term);
                }
                $terms[$value->type->term_type_name] = $term_transform;
            }
        }

        $texts = null;
        if (! empty($object->texts)) {
            $object_texts = $object->texts;
            $object_texts = $object_texts->groupBy('text_type_id');
            foreach ($object_texts as $text_type) {
                $text_transform = null;
                foreach ($text_type as $value) {
                    $text_transform[]= [
                        'text' => $value->text,
                    ];
                }
                $texts[$value->type->text_type_name] = $text_transform;
            }
        }

        return [
            'type'              => 'objects',
            'id'                => (int) $object->id,
            'uid'               => $object->object_uid,
            'title'             => $object->object_title,
            'name'              => $object->object_name,
            'description'       => $object->object_desc,
            'accession_num'     => $object->accession_num,
            'accession_date'    => $object->accession_date,
            'dimensions'        => $object->dimensions,
            'medium_display'    => $object->medium_display,
            'created_date'      => $object->created_date,
            'created_location'  => $object->created_location,
            'country'           => $object->country,
            'culture'           => $object->culture,
            'collection'        => $object->collection,
            'department'        => $object->department,
            'provenance'        => $object->provenance,
            'inscription'       => $object->inscription,
            'rights'            => $object->rights,
            'credit_line'       => $object->credit_line,
            'link_url'          => $object->link_url,
            'link_text'         => $object->link_text,
            'publish_web'       => $object->publish_web == 1 ? true : false,
            'can_zoom'          => $object->can_zoom == 1 ? true : false,
            'can_download'      => $object->can_download == 1 ? true : false,
            'on_view'           => $object->on_view == 1 ? true : false,
            'curator_verified'  => $object->curator_verified == 1 ? true : false,
            'terms'             => $terms,
            'texts'             => $texts,
            'actors'            => $actors,
            'assets'            => $asset_group,
            'dates'             => $dates,
            'locations'         => $locations,
            'custom'            => $object->object_custom,
            'created_at'        => $object->created_at,
            'updated_at'        => $object->updated_at,
        ];
    }

    public function transformDates($object_dates)
    {
        $dates = null;
        if (! empty($object_dates)) {
            $object_dates = $object_dates->groupBy('date_type_id');
            foreach ($object_dates as $date_type) {
                $date_transform = null;
                foreach ($date_type as $value) {
                    $date_transform[]= [
                        'date' => $value->date,
                        'timestamp' => $value->date_at
                    ];
                }
                $dates[$value->type->date_type_name] = $date_transform;
            }
        }
        return $dates;
    }

    public function transformLocations($object_locations)
    {
        $locations = null;
        if (! empty($object_locations)) {
            $object_locations = $object_locations->groupBy('location_type_id');
            foreach ($object_locations as $location_type) {
                $location_transform = null;
                foreach ($location_type as $value) {
                    $location_transform[]= [
                        'name' => $value->location,
                        'latitude' => $value->latitude,
                        'longitude' => $value->longitude
                    ];
                }
                $locations[$value->type->location_type_name] = $location_transform;
            }
        }
        return $locations;
    }

    public function collection($objects)
    {
        $data = null;

        foreach ($objects as $object) {
            $data[] = $this->transform($object);
        }
        $meta = [
            'total'         => $objects->total(),
            'per_page'      => $objects->perPage(),
            'current_page'  => $objects->currentPage(),
            'last_page'     => $objects->lastPage(),
        ];
        return ['meta' => $meta, 'data' => $data];
    }

    public function deleted($objects)
    {
        $data = null;

        foreach ($objects as $object) {
            $data[] = ['id' => $object->table_id];
        }
        $meta = [
            'total'         => $objects->total(),
            'per_page'      => $objects->perPage(),
            'current_page'  => $objects->currentPage(),
            'last_page'     => $objects->lastPage(),
        ];
        return ['meta' => $meta, 'data' => $data];
    }

    public function item($object)
    {
        return $data = $this->transform($object);
    }
}