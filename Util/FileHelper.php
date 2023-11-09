<?php
namespace App\Geonamesdump\Util;
use Symfony\Component\Process\Process;

class FileHelper
{
    /**
     * FileHelper constructor.
     */
    public function __construct(private readonly string $webdir, private readonly string|null $localdir, private readonly string $tmpdir)
    {
    }

    /**
     * Search custom local file or try in Symfony cache
     */
    public function searchForCustomFileOrCachedFile(string $file): bool
    {
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
     * Get local file or Download to Symfony cache dir
     * @throws \Exception
     */
    public function downloadFile(string $file): void
    {
        if($this->searchForCustomFileOrCachedFile($file)) return;
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
     * @throws \Exception
     */
    public function unzip(string $fileName, string $fromDir= null, string $endDir= null): FileHelper
    {
        $fromDir = $fromDir ? : $this->tmpdir;
        $endDir = $endDir ? : $fromDir;

        $unzip = new Process(['unzip', '-q', '-j', '-o', $fromDir.$fileName, '-d', $endDir]);
        $unzip->run();
        if(!$unzip->isSuccessful()){
            throw new \Exception($unzip->getErrorOutput());
        }
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function deleteTemporaryDir(): void
    {
        if(!file_exists($this->tmpdir)) return;
        $rmdir = new Process(['rm', '-R', $this->tmpdir]);
        $rmdir->run();
        if(!$rmdir->isSuccessful()) throw new \Exception($rmdir->getErrorOutput());
    }

    /**
     * Create temp dir in Symfony cache if not exists
     * @throws \Exception
     */
    public function createTemporaryDir(): void
    {

        if(file_exists($this->tmpdir)) return;
        if(!@mkdir($this->tmpdir, 0777, true)){
            $e = error_get_last();
            throw new \Exception($e['message']);
        }
    }

      public function getTmpdir(): string
    {
        return $this->tmpdir;
    }
    public function getLocaldir(): string
    {
        return $this->localdir;
    }

}