<?php
/* This file is part of Merzbau | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Merz;

use \SSITU\Euclid\EuclidCompanion;
use \SSITU\Jack\Jack;
class MerzbauCli
{
    private $Companion;
    private $Merzbau;
    private $callableMap;
    private $proflcallableMap;
    private $jobs;
    private $profiles;

    public function __construct($configPath, $run = true)
    {
        $config = Jack::File()->readJson($configPath);
        if (empty($config) || !array_key_exists('jobs', $config)) {
            exit('invalid config path or content: ' . $configPath);
        }
        $this->jobs = $config['jobs'];
        $this->profiles = $config['profiles'];
        $this->Companion = EuclidCompanion::inst();
        $this->Merzbau = new Merzbau($this->jobs);
        $this->callableMap = Jack::Arrays()->reIndex(array_keys($this->jobs), 1);
        $this->proflcallableMap = Jack::Arrays()->reIndex($this->profiles, 1);

        if ($run) {
            return $this->run();
        }
    }

    public function run()
    {
        $this->Companion->set_callableMap($this->callableMap);
        $this->Companion::echoDfltNav();
        $key = $this->Companion->printCallableAndListen();
        return $this->handleCmd($key);
    }

    private function destOpt($job)
    {
        $opts = $this->jobs[$job]["destinations_opts"];
        $opts['other']= '';
        $this->Companion->set_callableMap($opts);
        $this->Companion->msg('Pick a destination:','blue');
        $destK = $this->Companion->printCallableAndListen();
        if($destK === 'other'){
            return $this->destInput();
        }
        return $this->jobs[$job]["destinations_opts"][$destK];
    }

    private function destInput()
    {
        $this->Companion->set_callableMap([]);
        $this->Companion->msg('Enter a destination path:','blue');
        return $this->Companion->listenToRequest();
    }

    private function handleCmd($key)
    {
        $job = $this->callableMap[$key];
        $profile = '';
        if (!empty($this->jobs[$job]["param"]['profile']) && $this->jobs[$job]["param"]['profile'] == '{profile}') {
            
            $this->Companion->set_callableMap($this->proflcallableMap);
            $this->Companion->msg('Pick a profile:','blue');
            $profileK = $this->Companion->printCallableAndListen();
            $profile = $this->proflcallableMap[$profileK];
        }
        if(array_key_exists('destination',$this->jobs[$job]["param"]) && empty($this->jobs[$job]["param"]['destination'])){
            if(!empty($this->jobs[$job]["destinations_opts"])){
                $destn = $this->destOpt($job);
            } else {
                $destn = $this->destInput();              
            }
            $this->Merzbau->updateJobs($job, 'destination',  $destn);
        }

        $do = $this->Merzbau->runJob($job, $profile);
        $nextkey = $this->Companion->printRslt($do, false, true, $this->callableMap);
        return $this->handleCmd($nextkey);
    }

}
