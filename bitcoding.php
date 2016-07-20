<?php
/**
 * Created by PhpStorm.
 * User: Superuser
 * Date: 19.07.2016
 * Time: 09:19
 */

use bitcodin\Bitcodin;
use bitcodin\VideoStreamConfig;
use bitcodin\AudioStreamConfig;
use bitcodin\Job;
use bitcodin\JobConfig;
use bitcodin\Input;
use bitcodin\HttpInputConfig;
use bitcodin\EncodingProfile;
use bitcodin\EncodingProfileConfig;
use bitcodin\ManifestTypes;
use bitcodin\Output;
use bitcodin\FtpOutputConfig;
use bitcodin\S3OutputConfig;

require_once __DIR__.'/vendor/autoload.php';

if (isset($_POST['method']) && $_POST['method'] != "")
{
    $method = $_POST['method'];
    if ($method == "bitmovin_encoding_service") {

        $apiKey = $_POST['apiKey'];
        /*$output = $_POST['output'];
        $profile = $_POST['profile'];
        $video_width = $_POST['video_width'];
        $video_height = $_POST['video_height'];
        $video_bitrate = $_POST['video_bitrate'];
        $audio_bitrate = $_POST['audio_bitrate'];
        $video_src = $_POST['video_src'];

        switch ($output) {
            case ("ftp"):
                $ftp_server = $_POST['ftp_server'];
                $ftp_usr = $_POST['ftp_usr'];
                $ftp_pw = $_POST['ftp_pw'];
                break;
            case ("s3"):
                $access_key = $_POST['access_key'];
                $secret_key = $_POST['secret_key'];
                $bucket = $_POST['bucket'];
                $prefix = $_POST['prefix'];
                break;
        }*/
        bitmovin_encoding_service($apiKey);
    }
}

function bitmovin_encoding_service($apiKey) {

    // CONFIGURATION
    Bitcodin::setApiToken($apiKey);

    $inputConfig = new HttpInputConfig();
    $inputConfig->url = $_POST['video_src'];
    $input = Input::create($inputConfig);

    // CREATE VIDEO STREAM CONFIG
    $videoStreamConfig = new VideoStreamConfig();
    if (isset($_POST['video_height']) && $_POST['video_height'] != "")
    {
        $videoStreamConfig->height = (int)$_POST['video_height'];
    }
    if (isset($_POST['video_width']) && $_POST['video_width'] != "")
    {
        $videoStreamConfig->width = (int)$_POST['video_width'];
    }
    $videoStreamConfig->bitrate = (int)$_POST['video_bitrate'];

    // CREATE AUDIO STREAM CONFIGS
    $audioStreamConfig = new AudioStreamConfig();
    $audioStreamConfig->bitrate = (int)$_POST['audio_bitrate'];

    $encodingProfileConfig = new EncodingProfileConfig();
    $encodingProfileConfig->name = $_POST['profile'];
    $encodingProfileConfig->videoStreamConfigs[] = $videoStreamConfig;
    $encodingProfileConfig->audioStreamConfigs[] = $audioStreamConfig;

    // CREATE ENCODING PROFILE
    $encodingProfile = EncodingProfile::create($encodingProfileConfig);

    $jobConfig = new JobConfig();
    $jobConfig->encodingProfile = $encodingProfile;
    $jobConfig->input = $input;
    $jobConfig->manifestTypes[] = ManifestTypes::M3U8;
    $jobConfig->manifestTypes[] = ManifestTypes::MPD;

    // CREATE JOB
    $job = Job::create($jobConfig);

    //WAIT TIL JOB IS FINISHED
    do{
        $job->update();
        sleep(1);
    } while($job->status != Job::STATUS_FINISHED);

    if ($_POST['output'] == "ftp")
    {
        $outputConfig = new FtpOutputConfig();
        $outputConfig->name = "My Wordpress FTP Output";
        $outputConfig->host = $_POST['ftp_server'];
        $outputConfig->username = $_POST['ftp_usr'];
        $outputConfig->password = $_POST['ftp_pw'];
    }
    else    {
        $outputConfig = new S3OutputConfig();
        $outputConfig->name         = "My Wordpress S3 Output";
        $outputConfig->accessKey    = $_POST['access_key'];
        $outputConfig->secretKey    = $_POST['secret_key'];
        $outputConfig->bucket       = $_POST['bucket'];
        $outputConfig->prefix       = $_POST['prefix'];
        $outputConfig->makePublic   = false;
    }

    $output = Output::create($outputConfig);

    // TRANSFER JOB OUTPUT
    $job->transfer($output);
    echo "Video wurde nach " + $output->host + " übertragen.";
}

?>