<?php

namespace BCLib\BCBento;

use Elasticsearch\Client;

class GuidesService extends AbstractLocalService implements ServiceInterface
{
    const MIN_GUIDE_SCORE = '.3';

    public function __construct(Client $elastic_search)
    {
        parent::__construct($elastic_search);
        $this->index = 'guides';
    }

    public function buildQuery($keyword, array $taxonomy_terms)
    {
        // Increase to make lower-level taxonomy results comparatively more valuable.
        $level_boost_multiple = 10;

        // Increase to use more matched taxonomy terms.
        $terms_to_use = 3;

        $keyword_query = [
            'query_string' => [
                'query' => $keyword
            ]
        ];

        $level_boost = 1;
        $should = [];
        $must = [];
        foreach ($taxonomy_terms as $taxonomy_term) {
            $i = 0;
            while ($i < $terms_to_use && isset($taxonomy_term[$i])) {
                $should[] = [
                    'match_phrase' => [
                        'taxonomy' => [
                            'query' => $taxonomy_term[$i]['term'],
                            'boost' => $taxonomy_term[$i]['total'] * $level_boost
                        ]
                    ]
                ];
                $i++;
            }
            $level_boost *= $level_boost_multiple;
        }

        if (count($taxonomy_terms)) {
            $should[] = $keyword_query;
        } else {
            $must[] = $keyword_query;
        }

        $must[] = [
            "match" => [
                "group" => "LibGuides v1"
            ]
        ];

        $query = [
            'query' => [
                'bool' => []
            ]
        ];

        if (count($should)) {
            $query['query']['bool']['should'] = $should;
        }

        if (count($must)) {
            $query['query']['bool']['must'] = $must;
        }

        return $query;
    }

    public function buildResponse(array $subject_guides)
    {
        $results = [];

        $seen = [];

        foreach ($subject_guides['hits']['hits'] as $hit) {

            $title = htmlspecialchars_decode($hit['_source']['name']);

            if (isset($seen[$title])) {
                continue;
            }

            if ($hit['_score'] < GuidesService::MIN_GUIDE_SCORE) {
                break;
            }

            $results[] = [
                'title'       => $title,
                'url'         => $hit['_source']['url'],
                'description' => $hit['_source']['description'],
                'score'       => $hit['_score'],
            ];

            $seen[$title] = true;
        }

        return $results;
    }
}