<?php

namespace BCLib\BCBento;

use Guzzle\Http\Client;

class SpringshareService implements ServiceInterface
{

    public function fetch($keyword)
    {
        $client = new Client();
        $request = $client->get($this->url($keyword));
        $body = $request->send()->getBody();
        $pattern = '/^jQuery_BC_API\((.*)\)$/';
        $replacement = '$1';
        $springshare_response = json_decode(preg_replace($pattern, $replacement, $body));
        return $this->buildResponse($springshare_response, $keyword);
    }

    private function url($keyword)
    {
        $base = 'http://search-platform.libapps.com/lg2/select';
        $query_string = "json.wrf=jQuery_BC_API&wt=json&group=true&group.field=g&group.truncate=true&group.ngroups=true&group.limit=4&fq=s%3A94&q=(guide%3A($keyword))%5E6%20(page%3A($keyword))%5E5%20(subject%3A($keyword))%5E4%20(tag%3A($keyword))%5E4%20(allInOne%3A($keyword))%5E1%20";
        return $base . '?' . $query_string;
    }

    private function buildResponse($springshare_response, $keyword)
    {
        $groups_hash = $springshare_response->grouped->g;

        return [
            'total_results' => $groups_hash->ngroups,
            'search_link'   => "http://libguides.bc.edu/srch.php?q=$keyword",
            'dym'           => null,
            'items'         => array_map([$this, 'processGroup'], $groups_hash->groups)
        ];
    }

    private function processGroup($group_hash)
    {
        $docs_hash = $group_hash->doclist->docs;
        $first_doc = $docs_hash[0];

        $url_base = 'http://libguides.bc.edu/';

        $author = [
            'id'   => isset($first_doc->aid) ? $first_doc->aid : '',
            'name' => isset($first_doc->an) ? $first_doc->an : '',
        ];

        return [
            'title'    => $first_doc->guide,
            'url'      => isset($first_doc->slug) ? $url_base . $first_doc->slug : $url_base . 'c.php?g=' . $first_doc->g,
            'group'    => isset($first_doc->group) ? $first_doc->group : '',
            'type'     => isset($first_doc->guide_type) ? $first_doc->guide_type : '',
            'subjects' => isset($first_doc->subject) ? $first_doc->subject : [],
            'tags'     => isset($first_doc->tag) ? $first_doc->tag : [],
            'abstract' => isset($first_doc->gd) ? $first_doc->gd : '',
            'author'   => $author,
            'pages'    => array_map([$this, 'processDoc'], $docs_hash)
        ];
    }

    private function processDoc($doc_hash)
    {
        $url_base = 'http://libguides.bc.edu/';

        $numeric_url = $url_base . 'c.php?g=' . $doc_hash->g . '&p=' . $doc_hash->p;

        return [
            'title' => $doc_hash->page,
            'url'   => isset($doc_hash->pslug) ? $url_base . $doc_hash->pslug : $numeric_url
        ];
    }
}