<?php

require_once "private/mod_videos.php";

class Camera {
    function __construct($cam_name) {
        $this->name = $cam_name;
        $this->duration = conf_dvr()['video_file_duration'];
    }

    function name() {
        return $this->name;
    }

    function video_by_timestamp($time)
    {
        $row = db()->query('select id, cam_name, fname, UNIX_TIMESTAMP(created) as time, file_size ' .
                           'from videos '.
                           'where UNIX_TIMESTAMP(created) >= %d ' .
                               'and UNIX_TIMESTAMP(created) <= %d and ' .
                               'cam_name = "%s"',
                           $time - $this->duration + 1,
                           $time, $this->name);
        if ($row <= 0)
            return NULL;

        return new Video_file($this, $row['id'], $row['fname'],
                              $row['time'], $row['file_size']);
    }

    function find_back($time, $finished_only = false)
    {
        $row = db()->query('select id, cam_name, fname, UNIX_TIMESTAMP(created) as time, file_size ' .
                           'from videos '.
                           'where UNIX_TIMESTAMP(created) < %d ' .
                               'and cam_name = "%s" ' .
                               ($finished_only ? 'and file_size is not NULL ' : '') .
                            'order by id desc limit 1',
                           $time, $this->name);
        if ($row <= 0)
            return NULL;

        return new Video_file($this, $row['id'], $row['fname'],
                              $row['time'], $row['file_size']);
    }

    function find_next($time, $finished_only = false)
    {
        $row = db()->query('select id, cam_name, fname, UNIX_TIMESTAMP(created) as time, file_size ' .
                           'from videos '.
                           'where UNIX_TIMESTAMP(created) > %d ' .
                               'and cam_name = "%s" ' .
                               ($finished_only ? 'and file_size is not NULL ' : '') .
                            'order by id asc limit 1',
                           $time, $this->name);
        if ($row <= 0)
            return NULL;

        return new Video_file($this, $row['id'], $row['fname'],
                              $row['time'], $row['file_size']);
    }


}

class Video_file {
    function __construct($cam, $id, $fname, $time, $file_size) {
        $this->cam = $cam;
        $this->id = $id;
        $this->fname = $fname;
        $this->time = $time;
        $this->file_size = $file_size;
    }

    function prev() {
        $row = db()->query('select UNIX_TIMESTAMP(created) as time, id, fname, file_size ' .
                           'from videos ' .
                           'where id < %d and cam_name = "%s" ' .
                           'order by id desc limit 1',
                    $this->id, $this->cam->name());

        if ($row <= 0)
            return NULL;

        return new Video_file($this->cam, $row['id'], $row['fname'],
                              $row['time'], $row['file_size']);
    }

    function next() {
        $row = db()->query('select UNIX_TIMESTAMP(created) as time, id, fname, file_size ' .
                    'from videos ' .
                    'where id > %d and cam_name = "%s" ' .
                    'order by id asc limit 1',
                    $this->id, $this->cam->name());

        if ($row <= 0)
            return NULL;

        return new Video_file($this->cam, $row['id'], $row['fname'],
                              $row['time'], $row['file_size']);
    }

    function url() {
        return mk_url(['mod' => 'videos',
                       'time_position' => $this->time]);
    }

    function fname() {
        return $this->fname;
    }

    function base_name() {
        return basename($this->fname);
    }

    function size() {
        return $this->file_size;
    }

    function time() {
        return $this->time;
    }
}

function camera($cam_name) {
    static $cam = NULL;
    if ($cam)
        return $cam;

    foreach (conf_dvr()['cameras'] as $info)
        if ($info['name'] == $cam_name)
            $cam = new Camera($cam_name);
    return $cam;
}


class Mod_absent extends Module {

    function content($args = [])
    {
        $time_position = isset($args['time_position']) ? $args['time_position'] : NULL;
        $date_position = isset($args['date_position']) ? $args['date_position'] : NULL;
        if ($date_position)
            $time_position = $this->date_to_timestamp($date_position);
        $cam_name = isset($args['cam']) ? $args['cam'] : conf_dvr()['cameras'][0]['name'];

        $tpl = new strontium_tpl("private/tpl/mod_videos.html", conf()['global_marks']);
        $tpl->assign(NULL);

        foreach (conf_dvr()['cameras'] as $info) {
            $tpl->assign('select_cam', ['name' => $info['name'],
                                        'name_text' => $info['desc'],
                                        'selected' => ($info['name'] == $cam_name) ? 'SELECTED' : '']);
        }

        $cam = camera($cam_name);
        if (!$cam) {
            $tpl->assign('no_camera', ['name' => $cam_name]);
            return $tpl->result();
        }


        if (!$time_position) {
            $video = $cam->find_back(time(), true);
            $time_position = $video->time();
        }

        $tpl->assign('selector',
                    ['date_position' => $this->timestamp_to_date($time_position),
                     'now_url' => mk_url([]),
                    ]);

        $video = $cam->video_by_timestamp($time_position);
        if (!$video) {
            $tpl->assign('no_video');
        } else {
            $tpl->assign('video',
                        ['file' => $video->fname(),
                         'offset' => ($time_position - $video->time()),
                         ]);

            if (!$video->size())
                $tpl->assign('busy_video');
        }

        if ($video)
            $prev = $video->prev();
        else
            $prev = $cam->find_back($time_position);

        if ($prev) {
            $tpl->assign('prev_video',
                         ['info' => $this->timestamp_to_date($prev->time()),
                          'url' => $prev->url()]);
            if (!$prev->size())
                $tpl->assign('prev_busy_video');
        }

        if ($video)
            $next = $video->next();
        else
            $next = $cam->find_next($time_position);

        if ($next) {
            $tpl->assign('next_video',
                         ['info' => $this->timestamp_to_date($next->time()),
                          'url' => $next->url()]);

            if (!$next->size())
                $tpl->assign('next_busy_video');
        }

        return $tpl->result();
    }

    function timestamp_to_date($time)
    {
        return date("Y-m-d H:i:s", $time);
    }


    function date_to_timestamp($date)
    {
        return date_create_from_format("Y-m-d H:i:s", $date)->getTimestamp();
    }

}

modules()->register('videos', new Mod_absent);

