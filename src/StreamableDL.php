<?php

namespace Corbpie\StreamableDl;

use DOMDocument;

class StreamableDL
{
    private string $url;
    private string $save_as;
    private int $http_code;
    private string $video_direct_link;
    private string $html_page_content;
    private array $output_message = [];

    public function __construct(string $url, string $save_as)
    {
        $this->url = $url;
        $this->save_as = $save_as;
    }

    private function doCurl(string $url, string $referer): void
    {
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, $url);
        curl_setopt($crl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($crl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($crl, CURLOPT_HEADER, 1);
        $headers = [
            'Host: streamable.com',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*',
            'Accept-Encoding: gzip, deflate, br',
            'Accept-Language: en-US,en;q=0.5',
            'Cache-Control: no-cache',
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
            'Referer: ' . $referer,
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:93.0) Gecko/20100101 Firefox/93.0'
        ];
        curl_setopt($crl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($crl, CURLOPT_ENCODING, "");
        $this->html_page_content = curl_exec($crl);
        $this->http_code = curl_getinfo($crl, CURLINFO_HTTP_CODE);
        curl_close($crl);
        $this->output_message[] = [
            'date_time' => date('Y-m-d H:i:s'),
            'task' => __FUNCTION__,
            'args' => func_get_args(),
            'http_code' => $this->http_code
        ];
    }

    private function getVideoDirectLink(): void
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($this->html_page_content);
        $dl_link = $doc->getElementsByTagName('video')[0]->getAttribute('src');
        $this->video_direct_link = str_replace("//", "https://", $dl_link);
        $this->output_message[] = [
            'date_time' => date('Y-m-d H:i:s'),
            'task' => __FUNCTION__,
            'link' => $this->video_direct_link
        ];
    }

    private function saveVideoFile(): void
    {
        $result = file_put_contents($this->save_as, file_get_contents($this->video_direct_link));
        $this->output_message[] = [
            'date_time' => date('Y-m-d H:i:s'),
            'task' => __FUNCTION__,
            'args' => func_get_args(),
            'result' => $result
        ];
    }

    private function getVideoSizeFromUrl(): int
    {
        return get_headers($this->video_direct_link, true)['Content-Length'];
    }

    private function getVideoDetails(string $file_name): array
    {
        if (file_exists($file_name)) {
            $data = json_decode(shell_exec("ffprobe -v quiet -print_format json -show_format -show_streams $file_name"), true);

            if (isset($data['streams'])) {
                $first_stream = $data['streams'][0];
                $fr_array = explode('/', $first_stream['r_frame_rate']);//Splits frame rate string into array 60/1 -> 60,1
                (isset($data['streams'][1])) ? $has_audio = true : $has_audio = false;
                if ($has_audio) {//Has audio (stream)
                    $audio_codec = $data['streams'][1]['codec_name'];
                    $audio_bitrate = $data['streams'][1]['bit_rate'];
                    $audio_rate = $data['streams'][1]['sample_rate'];
                } else {
                    $audio_codec = $audio_bitrate = $audio_rate = null;
                }

                return [
                    'filename' => $data['format']['filename'] ?? null,
                    'media_link' => $file_name,
                    'height' => $first_stream['height'] ?? null,
                    'width' => $first_stream['width'] ?? null,
                    'framerate' => ($fr_array[0] / $fr_array[1]),
                    'total_frames' => $first_stream['nb_frames'] ?? null,
                    'size' => $data['format']['size'] ?? null,
                    'bitrate' => $first_stream['bit_rate'] ?? null,
                    'duration' => number_format($data['format']['duration'], 2),
                    'codec' => $first_stream['codec_name'] ?? null,
                    'bits' => $first_stream['bits_per_raw_sample'] ?? null,
                    'has_audio' => $has_audio,
                    'audio_codec' => $audio_codec,
                    'audio_bitrate' => $audio_bitrate,
                    'audio_rate' => $audio_rate
                ];

            }
        } else {
            return ["message" => "$file_name seems to be corrupt"];
        }
        return ["message" => "$file_name was not found"];
    }

    public function downloadVideo(string $referer = 'https://reddit.com/'): array
    {
        $this->doCurl($this->url, $referer);

        if ($this->http_code === 200) {
            $this->getVideoDirectLink();
            $this->saveVideoFile();
            $this->output_message[] = [
                'date_time' => date('Y-m-d H:i:s'),
                'task' => __FUNCTION__,
                'message' => 'Downloaded video',
                'saved_as' => $this->save_as
            ];
        } else {
            $this->output_message[] = [
                'date_time' => date('Y-m-d H:i:s'),
                'task' => __FUNCTION__,
                'message' => 'Failed to get video url',
                'http_code' => $this->http_code
            ];
        }

        return $this->output_message;
    }

}