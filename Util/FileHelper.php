<?php
namespace App\Geonamesdump\Util;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

class FileHelper
{
    /**
     * FileHelper constructor.
     */
    public function __construct(private readonly string $webdir, private readonly string $localdir, private readonly string $tmpdir)
    {
    }

    /**
     * Local search. First try Symfony cache, second: custom local dir
     * @param string $file filename
     * @return bool
     */
    public function getFileFromLocaldir($file){
        $from = $this->localdir.$file;
        $to = $this->tmpdir.$file;

        if(is_file($from))
        {
            $cp = new Process(['cp', $from, $to]);
            $cp->run();
            if(!$cp->isSuccessful()){
                return false;
            }else{
                return true;
            }
        }

        if(is_file($to)) return true;

        return false;

    }

    /**
     * Download to temp dir
     * @param string $file filename
     * @return void
     * @throws \Exception
     */
    public function downloadFile($file){
        if($this->getFileFromLocaldir($file)) return;
        elseif ($this->getCurlFile($file)) return ;
        elseif ($this->getWgetFile($file)) return ;
        else throw new \Exception('Download "%s" error!!!', $file);
    }

    /**
     * @throws \Exception
     */
    private function getWgetFile(string $file): bool
    {
        $from = $this->webdir.$file;
        $to = $this->tmpdir.$file;
        $wget = new Process(['wget', $from, '-O' , $to]);
        $wget->setTimeout(null);
        $wget->run();
        if(!$wget->isSuccessful()){
            throw new \Exception(sprintf(
                'Wget failed with error #%d: %s',
                $wget->getExitCode(), $wget->getErrorOutput()));
        }
        return true;
    }

    /**
     * @throws \Exception
     */
    private function getCurlFile(string $file): bool
    {
        if (!function_exists('curl_version')){
            return false;
        }
        $from = $this->webdir.$file;
        $to = $this->tmpdir.$file;
        set_time_limit(0);
        $fp = fopen ($to, 'w+');//This is the file where we save the information
        try {
            $ch = curl_init();
            if (FALSE === $ch){
                       throw new \Exception('failed to initialize Curl');
            }
            curl_setopt($ch,CURLOPT_URL,$from);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOPROGRESS, false); // needed to make progress function work
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            if (FALSE === curl_exec($ch)){
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }
            fclose($fp);
            curl_close($ch);
        } catch(\Exception $e) {
            throw new \Exception(sprintf(
                'Curl failed with error #%d: %s',
                $e->getCode(), $e->getMessage()),
                E_USER_ERROR);
        }
        return true;
    }

    /**
     * Command line unzip
     * @param string $file file name
     * @param string $from source directory
     * @param string $end end directory
     * @return $this
     * @throws \Exception
     */
    public function unzip($file, $from= null, $end= null){
        $from = $from ? : $this->tmpdir;
        $end = $end ? : $from;

        $unzip = new Process(['unzip', '-q', '-j', '-o', $from.$file, '-d', $end]);
        $unzip->run();
        if(!$unzip->isSuccessful()){
            throw new \Exception($unzip->getErrorOutput());
        }
        return $this;
    }

    /**
     * Grep lines to file in temporary dir
     * @param string $file Geonames country.txt file
     * @return $this
     */
    public function grepToFile(string $file, string $pattern, string $grepFile)
    {
        //$countryCode= explode(".", $file)[0];
        $endFile= $this->tmpdir.$grepFile;
        //$strArg= "$(printf '\\t')ADM3$(printf '\\t')";
        //$strArg= "/\\tADM3\\t/";
        $lines = preg_grep($pattern, file($this->tmpdir.$file));
        file_put_contents($endFile, $lines);
        return $this;
    }


    /**
     * Delete temp dir
     * @return void
     * @throws \Exception
     */
    public function deleteTemporaryDir(){
        if(!file_exists($this->tmpdir)) return;
        $rmdir = new Process(['rm', '-R', $this->tmpdir]);
        $rmdir->run();
        if(!$rmdir->isSuccessful()) throw new \Exception($rmdir->getErrorOutput());
        return;
    }

    /**
     * Create temp dir if not exists
     * @return void
     * @throws \Exception
     */
    public function createTemporaryDir(){

        if(file_exists($this->tmpdir)) return;
        if(!@mkdir($this->tmpdir, 0777, true)){
            $e = error_get_last();
            throw new \Exception($e['message']);
        }
        return;
    }
}