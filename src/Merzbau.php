<?php
/* This file is part of Merz | SSITU | (c) 2021 I-is-as-I-does | MIT License */

namespace SSITU\Merz;
use \SSITU\Jack;
class Merzbau implements Merzbau_i
{

    private $jobs;

    public function __construct($jobs)
    {
        $this->jobs = $jobs;
    }

    public function updateJobs($job, $paramKey, $paramVal)
    {
        if (array_key_exists($job, $this->jobs) && array_key_exists($paramKey, $this->jobs[$job]['param'])) {
            $this->jobs[$job]['param'][$paramKey] = $paramVal;
        }

    }

    public function runJob(string $jobId, string $profile = '')
    {
        if (!array_key_exists($jobId, $this->jobs)) {
            return ['err' => 'unknown job: ' . $jobId];
        }
        if (!array_key_exists('method', $this->jobs[$jobId])) {
            return ['err' => 'method has not been specified'];
        }
        $method = $this->jobs[$jobId]['method'];
        if (!method_exists($this, $method)) {
            return ['err' => 'unknown method :' . $method];
        }

        if (!array_key_exists('param', $this->jobs[$jobId])) {
            return ['err' => 'Params have not been specified'];
        }

        if (array_key_exists('profile', $this->jobs[$jobId]["param"]) && $this->jobs[$jobId]["param"]['profile'] == '{profile}') {
            $this->jobs[$jobId]["param"]['profile'] = $profile;
        }

        $param = $this->jobs[$jobId]["param"];
        return $this->$method(...$param);

    }

    private function globFiles($globPatterns, $profile)
    {
        $log = [];
        $filespaths = [];
        foreach ($globPatterns as $globPattern) {
            $globPattern = $this->replaceProfileHook($globPattern, $profile);
            $glob = glob($globPattern);
            if (!empty($glob)) {
                $filespaths = array_merge($filespaths, $glob);
            } else {
                $log[] = "no file match for pattern: " . $globPattern;
            }
        }
        return ['log' => $log, 'paths' => $filespaths];
    }

    private function replaceProfileHook($path, $profile)
    {
        if (stripos($path, '{profile}') !== false) {
            $path = str_replace('{profile}', $profile, $path);
        }
        return $path;
    }

    private function handlePrefixSuffix($path, $removeSuffix = '', $removePrefix = '', $addSuffix = '', $addPrefix = '', $profile = '')
    {
        $dir = dirname($path);
        $ext = '';
        if (!is_dir($path)) {
            $ext = '.' . Jack\File::getExt($path);
        }
        $base = basename($path, $ext);

        if (!empty($removeSuffix) && stripos($base, $removeSuffix) !== false) {
            $base = substr($base, 0, -strlen($removeSuffix));
        }
        if (!empty($removePrefix) && stripos($base, $removeSuffix) !== false) {
            $base = substr($base, strlen($removePrefix));
        }
        if (!empty($addSuffix)) {
            $base .= $addSuffix;
        }
        if (!empty($addPrefix)) {
            $base = $base . $addPrefix;
        }

        $base = $this->replaceProfileHook($base, $profile);

        return ['base' => $base, 'path' => $dir . '/' . $base . $ext];
    }

    protected function mergeJson($globPatterns, $destination, $removeSuffix = '', $removePrefix = '', $addSuffix = '', $addPrefix = '', $profile = '', $sortPages = true)
    {

        $glob = $this->globFiles($globPatterns, $profile);
        if (empty($glob['paths'])) {
            return ['anomaly' => 'no files found'];
        }
        $stock = [];
        foreach ($glob['paths'] as $path) {
            $content = Jack\File::readJson($path);
            if (empty($content)) {
                $glob['log'][] = 'either empty or invalid file: ' . $path;
                continue;
            }
            $rename = $this->handlePrefixSuffix($path, $removeSuffix, $removePrefix, $addSuffix, $addPrefix, $profile);
            $stock[$rename['base']] = $content;
        }
        if (empty($stock)) {
            return ['anomaly' => 'all files were either empty or invalid'];
        }

        if ($sortPages) {
            $stock = $this->sortArrayByKey($stock, 'priority');
            foreach ($stock as $k => $section) {

                $nitems = $this->sortNestedArrayByKey($section['items'], 'priority');
                $stock[$k]['items'] = $nitems;
            }
        }

        $destination = $this->replaceProfileHook($destination, $profile);

        $glob['log']['save'] = Jack\File::saveJson($stock, $destination, true);
        return $glob['log'];
    }

    private function sortNestedArrayByKey($arr, $key)
    {

        uasort($arr, function ($a, $b) use ($key) {
            if ($a[$key] == $b[$key]) {
                return 0;
            }

            return ($a[$key] > $b[$key]) ? 1 : -1;
        });
        return $arr;
    }

    private function sortArrayByKey($arr, $key)
    {

        $col = array_column($arr, $key);
        array_multisort($col, SORT_ASC, $arr);
        return $arr;
    }

    protected function mapPages($sectionsPath, $profile, $destination = null)
    {

        $sectionsPath = $this->replaceProfileHook($sectionsPath, $profile);

        $sections = Jack\File::readJson($sectionsPath);
        if (empty($sections)) {
            return ['err' => 'invalid path or content: ' . $sectionsPath];
        }
        $remap = [];
        foreach ($sections as $sectionId => $sectionData) {
            foreach ($sectionData['items'] as $pageId => $pagedata) {
                $remap[$pageId]['controller'] = $pagedata['controller'];
                $remap[$pageId]['section'] = $sectionData['auth'];
                $remap[$pageId]['status'] = $sectionData['status'];
            }

        }

        if (empty($destination)) {
            $destination = substr($sectionsPath, -5) . '-index.json';

        } else {
            $destination = $this->replaceProfileHook($destination, $profile);

        }
        return Jack\File::saveJson($remap, $destination, true);
    }

    protected function recursiveRename($globPatterns, $removeSuffix = '', $removePrefix = '', $addSuffix = '', $addPrefix = '', $profile = '')
    {
        $glob = $this->globFiles($globPatterns, $profile);
        if (empty($glob['paths'])) {
            return ['anomaly' => 'no files found'];
        }
        $log = [];
        foreach ($glob['paths'] as $path) {
            $rename = $this->handlePrefixSuffix($path, $removeSuffix, $removePrefix, $addSuffix, $addPrefix, $profile);
            $log[$rename['base']] = rename($path, $rename['path']);

        }
        return $log;
    }

}
