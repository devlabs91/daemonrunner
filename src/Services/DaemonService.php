<?php 

namespace Devlabs91\Daemonrunner\Services;
use Clio;

class DaemonService {
    
    private static $path = "/tmp/daemonservice";
    private static $umask = '0000';
    private static $mode = '0777';
    
    private static $service;
    
    /**
     * Call this to request Service to exit.
     * @param string $filename
     */
    public static function requestExitService( $filename ) {
        if( !self::exitService( $filename ) ) {
            $old = umask( self::$umask );
            touch( self::getFileName( $filename.'.exit' ) );
            chmod( self::getFileName( $filename.'.exit' ), self::$mode);
            umask( $old );
        }
    }
    
    /**
     * 
     * Call this function from your service, to check if you should exit.
     * 
     * @param string $filename
     * @return boolean
     */
    public static function exitService( $filename ) {
        if( file_exists( self::getFileName( $filename.'.exit' ) ) ) {
            return true;
        }
        return false;
    }
    
    /**
     * 
     * Call this function from the Service when you are ready to exit
     * 
     * @param string $filename
     */
    public static function confirmExitService( $filename ) {
        self::unlinkFiles( $filename );
        exit;
    }
    
    /**
     * 
     * @param string $filename
     * @return boolean
     */
    public static function isRunning( $filename ) {
        $pid = self::getFileName( $filename.'.pid' );
        if( Clio\Daemon::isRunning( $pid ) ) { return true; }
        else { self::unlinkFiles( $filename ); }
        return false;
    }
    
    public static function getPid( $filename) {
        $filename = self::getFileName( $filename.'.pid' );
        if( file_exists( $filename ) ) { return file_get_contents( $filename ); }
        return null;
    }
    
    /**
     * 
     * @param string $filename
     * @param object $service
     * @return boolean
     */
    public static function start( $filename, $service ) {
        if( self::isRunning( $filename ) ) { return false; }
        self::$service = $service;
        Clio\Daemon::work( [ 'pid' => self::getFileName( $filename.'.pid' ), ],
            function($stdin, $stdout, $sterr) { 
                $service = clone self::$service;
                $service->runService();
            }
        );
        if( self::isRunning( $filename ) ) { return true; }
        return false;
    }
    
    /**
     * 
     * @param string $filename
     */
    public static function stop( $filename ) {
        while(1) {
            if( self::isRunning( $filename ) ) { self::kill( $filename ); }
            else { self::unlinkFiles($filename); break; }
        }
    }
    
    /**
     * 
     * @param string $filename
     */
    public static function kill( $filename ) {
        Clio\Daemon::kill( self::getFileName( $filename.'.pid' ), true);
    }
    
    /**
     * 
     * @param string $text
     */
    public static function iamalive( string $text ) {
        $content = '['.(new \DateTime())->format( 'Y-m-d H:i:s' ).']'.' '.$text.PHP_EOL;
        file_put_contents( self::getLogName('runner.log'), $content, FILE_APPEND );
    }
    
    /**
     *
     * @param string $filename
     * @return string
     */
    public static function getLogName( $filename ) {
        $fullFilename = self::$path.'/'.$filename;
        if( ! is_dir( dirname( $fullFilename ) ) ) { mkdir( dirname( $fullFilename ), 0755, true ); }
        return $fullFilename;
    }
    
    /**
     *
     * @param string $filename
     * @return string
     */
    public static function getFileName( $filename ) {
        $fullFilename = self::$path.'/'.$filename;
        if( ! is_dir( dirname( $fullFilename ) ) ) {
            $old = umask( self::$umask );
            mkdir( dirname( $fullFilename ), self::$mode, true );
            umask( $old );
        }
        return $fullFilename;
    }
    
    /**
     * 
     * @param string $filename
     */
    public static function unlink( $filename ) {
        $fullFilename = self::getFileName($filename);
        if( file_exists( $fullFilename ) ) { unlink( $fullFilename ); }
    }
    
    /**
     * 
     * @param string $filename
     */
    public static function unlinkFiles( $filename ) {
        self::unlink($filename.".exit");
        self::unlink($filename.'.pid');
    }
    
}
