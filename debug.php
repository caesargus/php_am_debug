<?php
// NOTE: This was a debug file that I used to use to help me debug files when I used to work as a php developer
// Some of the notes below may not be relevant (the file is over 3 years old and several jobs back)
// I believe I had a nicer script at some point, but I don't recall where it was ...
// NOTE: I know there are a lot of improvements that I can make to it (PHP_EOL, random code cleanup)
// NOTE: Putting it in /tmp since I'm not sure what permissions that I acutally have to the server
// NOTE: I apologize for the horrible code
// NOTE: I swear all of this stuff seemed like a good idea at one point ...
/*
include_once '/tmp/code/php_debug/debug.php';
$debug = array();
$debug['attempt'] = $attempt;
$debug['response'] = $response;
\AMDebug::writeToDebugLog(__LINE__,__FUNCTION__,__FILE__,array('Debug' => $debug), false);

// why is there a \AMDebug ...? Was I trying to escape something? Namespace?
 */
// --- place on the public/index.php
// include_once '/tmp/code/debug.php';
// writeToDebugLog(__LINE__,__FUNCTION__,__FILE__,array('Debug' => $debug), true);
//
// --- place on the area that you're working to debug
// $debug = array();
//
// --- repeat as necessary
// $debug['whateverVariable'] = $whateverVariable;
//
// --- place wherever you need to track what just happened
// writeToDebugLog(__LINE__,__FUNCTION__,__FILE__,array('Debug' => $debug), false);
//
class AMDebug {
    private $logFile = '/tmp/am_php_debugLog.log';
    private $fileNameShortnerString = '/var/www/';
    private $depthBeforeMaxDepth = 5;
    public static function writeToDebugLog($line,$function,$file, $otherData, $newfile = false, $limitBacktraceLineCount = 3) {

        $flag = FILE_APPEND;
        $myOut = 'Date/Time: ' . date('Y-m-d H:i') . "\n";
        if ($newfile) {
            $flag = null;
        }
        $file = self::fileNameShortner($file);
        $backtrace = debug_backtrace();
        // $backtrace = "";
        $myOut .= <<<OUT
FILE: {$file}:{$line}
FUNCTION: {$function}
OUT;
        $nl = "\n";
        $debug = "BACKTRACE: {$nl} ============================================ {$nl}";
        $lineCount = 0;
        foreach ($backtrace as $backIndex => $backInfo)
        {
            if ($lineCount ++ > $limitBacktraceLineCount) {
                $debug .= "---- MORE ----" . "\n";
                break;
            }

            $filename = self::fileNameShortner($backInfo['file']);
            $debug .= "\t at {$backIndex} " . self::fileNameShortner($filename) . " (line {$backInfo['line']})"
                . " -> {$backInfo['function']}{$nl}";
    ;
    //           . "  -> {$backInfo['function']}(" . join(",",$backInfo['args']) . "){$nl}";
        }
        file_put_contents(
            $this->logFile,
            $myOut . "\n" . "======" . "\n"
            . $debug . "\n" . "=======" . "\n"
            . self::walkArray($otherData, 0) . "\n"
            . '======' . "\n",
            $flag);
    }


    private static function fileNameShortner($filename) {
        if( ($x_pos = strpos($filename, $this->fileNameShortnerString)) !== FALSE ) {
            $filename = substr($filename, $x_pos + strlen($this->fileNameShortnerString) );
        }
        return $filename;
    }

    private static function walkArray($data, $depth) {
        if ($depth > $this->depthBeforeMaxDepth) return 'Max Depth Reached';
        switch (gettype($data)) {
            case 'object':
                if ($data instanceof DateTime) {
                    return $data->format('Y-m-d H:i:s') . "\t" . '{' . gettype($data) . '}';
                }

                $return = "\n" ;
                $return .= self::getDepth($depth);
                $return .= ">>>>>> Object\n";
                $return .= self::getDepth($depth);
                $return .= "Data Type: {" . gettype($data) . "}\n";
                $return .= self::getDepth($depth);
                $return .= "=======================================" . "\n";
                foreach($data as $key => $entry) {
                    $return .= self::getDepth($depth);
                    $return .= $key . '(k)' . "\t" . "{" . gettype($key) . "}"  . "\n";
                    $return .= self::getDepth($depth);
                    $return .= "    " . ' => (e)'  . "\t"
                        . self::walkArray(self::dismount($data), $depth + 1) .  "\n";
                    /*
                    // I have no idea what I was trying to do here ...
                    if (!in_array($key, array(
                        'typeClass',
                        'association',
                    ))) { 
                    */
                    // }
                }

                $return .= self::getDepth($depth);
                $return .= "<<<<<< Object". "\n";
                return $return;
            case 'array':
                $return = "\n" ;
                $return .= self::getDepth($depth);
                $return .= ">>>>>> Array \n";
                $return .= self::getDepth($depth);
                $return .= "Data Type: {" . gettype($data) . "}\n";
                $return .= self::getDepth($depth);
                $return .= "=======================================" . "\n";
                foreach($data as $key => $entry) {
                $return .= self::getDepth($depth);
                    $return .= $key . '(k)' . "\t" . "{" . gettype($key) . "}"  . "\n";
                $return .= self::getDepth($depth);
                    $return .= "    " . ' => (e)'  . "\t" . self::walkArray($entry, $depth + 1) .  "\n";
                }

                $return .= self::getDepth($depth);
                $return .= "<<<<<< Array ". "\n";
                return $return;
            default:
                return $data . "\t" . '{' . gettype($data) . '}';

        }
    }

    private static function getDepth ($depth) {
        $return = "";
        for ($i = 0; $i < $depth; $i++) {
            $return .= "\t";
        }
        return $return;
    }

    private static function object_to_array($data)
    {
        if (is_array($data) || is_object($data))
        {
            $result = array();
            foreach ($data as $key => $value)
            {
                $result[$key] = self::object_to_array($value);
            }
            return $result;
        }
        return $data;
    }

    private static function dismount($object) {
        $reflectionClass = new ReflectionClass(get_class($object));
        $array = array();
        foreach ($reflectionClass->getProperties() as $property) {
            $property->setAccessible(true);
            $array[$property->getName()] = $property->getValue($object);
            $property->setAccessible(false);
        }
        return $array;
    }
}
