# streamable download

A PHP class to download streamable videos from URL

## Usage

Install with:

```composer require corbpie/streamable-dl```

Use like:

```php
<?php
require('vendor/autoload.php');

use Corbpie\StreamableDl\StreamableDL;
```

#### Download a video

```php
$url = "https://streamable.com/8mr65";//200 link

$save_as = "test.mp4";

$sdl = new StreamableDL($url, $save_as);

echo json_encode($sdl->downloadVideo());
```

The output will be:

```json
[
  {
    "date_time": "2021-10-27 09:43:25",
    "task": "doCurl",
    "args": [
      "https:\/\/streamable.com\/8mr65"
    ],
    "http_code": 200
  },
  {
    "date_time": "2021-10-27 09:43:25",
    "task": "getVideoDirectLink",
    "link": "https:\/\/cdn-cf-east.streamable.com\/video\/mp4\/8mr65.mp4?Expires=1635546720&Signature=XYZABC123&Key-Pair-Id=ABC098"
  },
  {
    "date_time": "2021-10-27 09:43:36",
    "task": "saveVideoFile",
    "args": [],
    "result": 3610791
  },
  {
    "date_time": "2021-10-27 09:43:36",
    "task": "downloadVideo",
    "message": "Downloaded video",
    "saved_as": "test.mp4"
  }
]
```

If you have an invalid URL (404 HTTP code):

```json
[
  {
    "date_time": "2021-10-27 09:51:55",
    "task": "doCurl",
    "args": [
      "https:\/\/streamable.com\/ABC123",
      "https:\/\/reddit.com\/"
    ],
    "http_code": 404
  },
  {
    "date_time": "2021-10-27 09:51:55",
    "task": "downloadVideo",
    "message": "Failed to get video url",
    "http_code": 404
  }
]
```

**Note** To get video file information with ```getVideoDetails()``` you need FFmpeg (FFprobe) installed.
