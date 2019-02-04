<?php 

namespace Devlabs91\Daemonrunner\Services;

class DemoService {
    
    public $filename;
    
    public function __construct( $filename ) {
        $this->filename = $filename;
    }
    
    public function runService() {
        $cnt = 1;
        while(1) {
            DaemonService::iamalive( $this->filename.'|'.$cnt );$cnt++;
            sleep(1);
            if( DaemonService::exitService( $this->filename ) ) { 
                DaemonService::confirmExitService( $this->filename );
            }
        }
    }
}
