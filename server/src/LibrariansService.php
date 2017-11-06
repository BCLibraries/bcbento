<?php

namespace BCLib\BCBento;

use Elasticsearch\Client;

class LibrariansService extends AbstractLocalService
{

    const MIN_LIBRARIAN_SCORE = '.1';

    public $max_boost = 0;

    public function __construct(Client $elastic_search, $es_version)
    {
        parent::__construct($elastic_search, $es_version);
        $this->index = 'librarians';
    }


    public function buildQuery($keyword, array $taxonomy_terms)
    {
        $must = [];
        $should = $this->buildTaxonomySubQueries($taxonomy_terms);

        $keyword_query = [
            'match' => [
                'subjects' => [
                    'query' => $keyword,
                    'boost' => $this->max_boost
                ]
            ]
        ];

        $phrase_match_query = [
            'match_phrase' => [
                'taxonomy' => [
                    'query' => $keyword,
                    'boost' => $this->max_boost
                ]
            ]
        ];

        if (count($taxonomy_terms)) {
            $should[] = $keyword_query;
        } else {
            $must[] = $keyword_query;
        }

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

    public function buildResponse(array $librarians)
    {
        $results = [];
        foreach ($librarians['hits']['hits'] as $hit) {
            if ($hit['_score'] < self::MIN_LIBRARIAN_SCORE) {
                break;
            }

            $source = $hit['_source'];

            $librarian = [
                'id'       => $hit['_id'],
                'name'     => $source['first_name'] . ' ' . $source['last_name'],
                'image'    => $this->buildImageUrl($source),
                'email'    => $source['email'],
                'score'    => $hit['_score'],
                'subjects' => $source['subjects']
            ];
            $results[] = $librarian;
        }

        return $results;
    }

    protected function buildTaxonomySubQueries(array $taxonomy_terms)
    {
        // Increase to make lower-level taxonomy results comparatively more valuable.
        $level_boost_multiple = 5;

        // Increase to use more matched taxonomy terms.
        $terms_to_use = 2;

        $level_boost = 1;

        $taxonomy_queries = [];
        foreach ($taxonomy_terms as $taxonomy_term) {
            $i = 0;
            while ($i < $terms_to_use && isset($taxonomy_term[$i])) {
                $boost = $this->calcuateBoost($taxonomy_term[$i], $level_boost);
                $taxonomy_queries[] = [
                    'match_phrase' => [
                        'taxonomy' => [
                            'query' => $taxonomy_term[$i]['term'],
                            'boost' => $boost
                        ]
                    ]
                ];
                $i++;
            }
            $level_boost *= $level_boost_multiple;
        }
        return $taxonomy_queries;
    }

    private function calcuateBoost($taxonomy_term, $level_boost)
    {
        $boost = $taxonomy_term['total'] * $level_boost;
        $this->max_boost = $boost > $this->max_boost ? $boost : $this->max_boost;
        return $boost;
    }

    private function buildImageUrl($source)
    {
        if (!$source['image']) {
            return '';
        }

        if (strpos($source['image'], 'http://') === 0) {
            return str_replace('http://', '', $source['image']);
        }

        return 'library.bc.edu/staff-portraits/' . $source['image'];
    }

}