<?php

namespace BCLib\BCBento\Website;

class Page
{
    public $id;
    public $title;
    public $updated;
    public $text;
    public $url;

    /**
     * @var Guide
     */
    public $guide;

    public function crawl()
    {
        $this->text = '';

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_USERAGENT,'BostonCollegeLibrariesBot');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        $result = curl_exec($ch);
        curl_close($ch);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($result);
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//div[@class="s-lib-box-container"]') as $node) {
            $this->text .= $node->textContent;
        };
        $this->text = str_replace("\n",' ', $this->text);
        $this->text = str_replace("\t", " ", $this->text);
        $this->text = preg_replace("/  +/", " ",$this->text);
    }
}