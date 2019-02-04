<?php

namespace Devlabs91\Daemonrunner\Tests\PHPUnit;

use Devlabs91\Daemonrunner\Services\DaemonService;
use Devlabs91\Daemonrunner\Services\DemoService;

class DaemonServiceTest extends \PHPUnit_Framework_TestCase
{
    
    public function testDaemonGetPidFile() {
        
        $filename = 'test/config.yml';
        $fullFilename = DaemonService::getFileName( $filename );
        $this->assertTrue( is_dir( dirname( $fullFilename ) ) );
        
    }

    public function testDaemonStart() {

        $filename = 'test/config.yml';
        $service = $this->initService( $filename );
        
        $result = DaemonService::start( $filename, $service );
        $this->assertTrue( $result );
        
        sleep(4);
        $content = file_get_contents( '/tmp/daemonservice/runner.log' );
        $this->assertGreaterThanOrEqual(160, strlen($content));
        
        DaemonService::stop( $filename );
        
    }
    
    public function testRequestExitService() {

        $filename = 'test/config.yml';
        $service = $this->initService( $filename );

        $result = DaemonService::start( $filename, $service );
        $this->assertTrue( $result );
        
        sleep(4);
        $content = file_get_contents( '/tmp/daemonservice/runner.log' );
        $this->assertGreaterThanOrEqual(160, strlen($content));
        
        DaemonService::requestExitService( $filename );
        while(1) {
            if( !DaemonService::isRunning($filename) ) { break; }
            sleep(1);
        }
        
    }
    
    protected function initService( $filename ) {
        $runnerlog = '/tmp/daemonservice/runner.log';
        if( file_exists( $runnerlog) ) { unlink( $runnerlog ); }
        $service = new DemoService( $filename );
        if( DaemonService::isRunning($filename) ) { DaemonService::stop( $filename ); }
        return $service;
    }
    
}