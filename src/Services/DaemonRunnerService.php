<?php 

namespace Devlabs91\Daemonrunner\Services;
use Clio;

class DaemonRunnerService {
    
    private static $commands = array("start", "stop", "restart", "kill", "clean", "status");
    
    public static function getCommands() {
        return self::$commands;
    }
    
    public static function execute( $command, $filenames, $class, $cliOutput = true ) {
        if($command=="start") {
            DaemonRunnerService::startDaemons( $filenames, $class, $cliOutput );
        } else if ($command=="stop") {
            DaemonRunnerService::stopDaemons( $filenames, false, $cliOutput );
        } else if ($command=="restart") {
            DaemonRunnerService::restartDaemons( $filenames, $class, false, $cliOutput );
        } else if ($command=="kill") {
            DaemonRunnerService::stopDaemons( $filenames, true, $cliOutput );
        } else if ($command=="clean") {
            DaemonRunnerService::cleanDaemons( $filenames, $cliOutput );
        } else if ($command=="status") {
            DaemonRunnerService::statusDaemons( $filenames, $cliOutput );
        }
        
    }
    
    public static function restartDaemons( $filenames, $class, $force = false, $cliOutput = true ) {
        foreach($filenames AS $filename) {
            self::restart( $filename, $class, $force, $cliOutput );
        }
    }
    
    public static function restart( $filename, $class, $force = false, $cliOutput = true ) {
        $pid = DaemonService::getPid($filename);
        if( DaemonService::isRunning( $filename ) ) {
            if($cliOutput) { Clio\Console::output("%B[".$filename."]%n%b is running with pid: ".$pid."%n"); }
        } else {
            if($cliOutput) { Clio\Console::output("%R[".$filename."]%n%r is not running%n"); }
        }
        self::stop($filename, $force, $cliOutput);
        self::start($filename, $class, $cliOutput);
    }
    
    public static function startDaemons( $filenames, $class, $cliOutput = true ) {
        foreach($filenames AS $filename) {
            self::start( $filename, $class, $cliOutput );
        }
    }
    
    public static function start( $filename, $class, $cliOutput = true ) {
        
        if( DaemonService::isRunning( $filename ) ) {
            if($cliOutput) {
                Clio\Console::output("%B[".$filename."]%n%b process is already running with pid: ".DaemonService::getPid($filename).".%n");
            }
            return false;
        }
        if( DaemonService::exitService( $filename ) ) {
            if($cliOutput) {
                Clio\Console::output("%R[".$filename."]%n%r make sure process are not running, and run a clean.%n");
            }
            return false;
        }
        $service = new $class( $filename );
        DaemonService::start( $filename, $service );
        while(1) {
            if( DaemonService::isRunning( $filename ) ) {
                if($cliOutput) {
                    Clio\Console::output("%B[".$filename."]%n%b Process started.%n");
                }
                return true;
            } else { usleep(2000000); }
        }
        return false;        
    }
    
    public static function stopDaemons( $filenames, $force=false, $cliOutput = true ) {
        foreach($filenames AS $filename) {
            self::stop( $filename, $force, $cliOutput );
        }
    }
    
    public static function stop( $filename, $force=false, $cliOutput = true ) { 
        $pid = DaemonService::getPid($filename);
        DaemonService::requestExitService( $filename );
        $cnt = 0;
        while(1) {
            if( DaemonService::isRunning( $filename ) ) {
                if($cliOutput) { Clio\Console::output("%B[".$filename."]%n%b wait, trying to stop process ".$pid.".%n"); }
                if($cnt>5 && $force) {
                    if($cliOutput) { Clio\Console::output("%R[".$filename."]%n%r trying to kill process ".$pid.".%n"); }
                    DaemonService::kill( $filename );
                }
            } else {
                if($cliOutput) { Clio\Console::output("%R[".$filename."]%n%r process ".$pid." has been terminated.%n"); }
                DaemonService::unlinkFiles( $filename );
                break;
            }
            usleep(2000000);$cnt++;
        }
    }
    
    public static function statusDaemons( $filenames, $cliOutput = true ) {
        foreach($filenames AS $filename) {
            self::status( $filename, $cliOutput );
        }
    }
    
    public static function status($filename, $cliOutput = true) {
        $pid = DaemonService::getPid($filename);
        if( DaemonService::isRunning( $filename ) ) {
            if($cliOutput) { Clio\Console::output("%B[".$filename."]%n%b is running with pid: ".$pid."%n"); }
        } else {
            if($cliOutput) { Clio\Console::output("%R[".$filename."]%n%r is not running%n"); }
        }
    }
    
    public static function cleanDaemons( $filenames, $cliOutput = true ) {
        foreach($filenames AS $filename) {
            self::clean( $filename, $cliOutput );
        }
    }
    
    public static function clean( $filename, $cliOutput = true ) {
        if( !DaemonService::isRunning( $filename ) ) {
            if($cliOutput) { Clio\Console::output("%R[".$filename."]%n%r cleanup, pid: ".DaemonService::getPid( $filename ).".%n"); }
            DaemonService::unlinkFiles( $filename );
        }
    }
    
}