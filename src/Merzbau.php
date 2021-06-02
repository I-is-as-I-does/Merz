<?php
/* This file is part of Merz | SSITU | (c) 2021 I-is-as-I-does | MIT License */

namespace SSITU\Merz;

use SSITU\Jack\Jack;

class Merzbau  {

    private $config;
   
    public function __construct($configPath){
        $config = Jack::File()->readJson($configPath);
        if(empty($config) || !array_key_exists('jobs',$config)){
            return ['err'=>'invalid config path or content: '.$configPath];
        }
        $this->config = $config["jobs"];
    }

    public function runJob(string $jobId, string $profile = ''){
        if(!array_key_exists($jobId, $this->config)){
            return ['err'=>'unknown job: '.$jobId];
        }
            if(!array_key_exists('method',$this->config[$jobId])){
                return ['err'=>'method has not been specified'];
            }
            $method = $this->config[$jobId]['method'];
            if(!method_exists($this,$method)){
                return ['err'=>'unknown method :'.$method];
            }

            if(!array_key_exists('param',$this->config[$jobId])){
                return ['err'=>'Params have not been specified'];
            }

       
            if(array_key_exists('profile',$this->config[$jobId]["param"]) && $this->config[$jobId]["param"]['profile'] == '{profile}'){
                $this->config[$jobId]["param"]['profile'] = $profile;
            }  
            
            $param = $this->config[$jobId]["param"]; 
            return $this->$method(...$param);

    }

    protected function mergeJson($globPatterns, $destination, $removeSuffix ='', $removePrefix ='', $profile =''){

        $log = [];
        $filespaths = [];
        foreach($globPatterns as $globPattern){
            if(stripos($globPattern, '{profile}') !== false){
                $globPattern = str_replace('{profile}',$profile,$globPattern);
            }
            $glob = glob($globPattern);
            if(!empty($glob)){
            $filespaths = array_merge($filespaths, $glob);
        } else {
            $log[] = "no file match for pattern: ".$globPattern;
        }
        }
        if(empty($filespaths)){
            return ['anomaly'=>'no files found'];
        }
        $stock = [];
        foreach($filespaths as $path){
            $content = Jack::File()->readJson($path);
            if(empty($content)){
                $log[] = 'either empty or invalid file: '.$path;
                continue;
            }
            $id = basename($path, '.json');
            if(!empty($removeSuffix) && stripos($id,$removeSuffix) !== false){
                $id = substr($id,0,-strlen($removeSuffix));  
            }
            if(!empty($removePrefix) && stripos($id,$removeSuffix) !== false){
                $id = substr($id,strlen($removePrefix));  
            }
            $stock[$id] = $content;
        }
        if(empty($stock)){
            return ['anomaly'=>'all files were either empty or invalid'];
        }
        if(stripos($destination, '{profile}') !== false){
            $destination = str_replace('{profile}',$profile,$destination);
        }
        $log['save'] = Jack::File()->saveJson($stock, $destination,true);
       return $log;
    }

}