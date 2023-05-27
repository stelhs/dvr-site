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
        $row = db()->query('select id, cam_name, fname, UNIX_TIMESTAMP(created) as time, file_size, duration ' .
                           'from videos '.
                           'where UNIX_TIMESTAMP(created) >= %d ' .
                               'and UNIX_TIMESTAMP(created) <= %d and ' .
                               'cam_name = "%s"',
                           $time - $this->duration + 1,
                           $time, $this->name);
        if ($row <= 0)
            return NULL;

        return new Video_file($this, $row['id'], $row['fname'],
                              $row['time'], $row['file_size'],
                              $row['duration']);
    }

    function find_back($time, $finished_only = false)
    {
        $row = db()->query('select id, cam_name, fname, UNIX_TIMESTAMP(created) as time, file_size, duration ' .
                           'from videos '.
                           'where UNIX_TIMESTAMP(created) < %d ' .
                               'and cam_name = "%s" ' .
                               ($finished_only ? 'and file_size is not NULL ' : '') .
                            'order by id desc limit 1',
                           $time, $this->name);
        if ($row <= 0)
            return NULL;

        return new Video_file($this, $row['id'], $row['fname'],
                              $row['time'], $row['file_size'],
                              $row['duration']);
    }

    function find_next($time, $finished_only = false)
    {
        $row = db()->query('select id, cam_name, fname, UNIX_TIMESTAMP(created) as time, file_size, duration ' .
                           'from videos '.
                           'where UNIX_TIMESTAMP(created) > %d ' .
                               'and cam_name = "%s" ' .
                               ($finished_only ? 'and file_size is not NULL ' : '') .
                            'order by id asc limit 1',
                           $time, $this->name);
        if ($row <= 0)
            return NULL;

        return new Video_file($this, $row['id'], $row['fname'],
                              $row['time'], $row['file_size'],
                              $row['duration']);
    }

    function timelapses_by_timestamp($time)
    {
        $q = sprintf('select id, interval_name, '.
                            'fname, video_duration, '.
                            'UNIX_TIMESTAMP(start) as start_time, '.
                            'UNIX_TIMESTAMP(end) as end_time, '.
                            'progress_duration, file_size '.
                    'from timelapses '.
                    'where start <= FROM_UNIXTIME(%d) and end >= FROM_UNIXTIME(%d) and '.
                    'cam_name = "%s"', $time, $time, $this->name);
        $rows = db()->query_list($q);
        if ($rows <= 0)
            return NULL;

        $list = [];
        foreach ($rows as $row) {
            $list[] = new Timelapse_file($this, $row['id'], $row['interval_name'],
                                         $row['fname'], $row['video_duration'],
                                         $row['start_time'], $row['end_time'],
                                         $row['progress_duration'], $row['file_size']);
        }
        return $list;
    }


}

class Video_file {
    function __construct($cam, $id, $fname, $time, $file_size, $duration) {
        $this->cam = $cam;
        $this->id = $id;
        $this->fname = $fname;
        $this->time = $time;
        $this->file_size = $file_size;
        $this->duration = $duration;
    }

    function prev() {
        $row = db()->query('select UNIX_TIMESTAMP(created) as time, id, fname, file_size, duration ' .
                           'from videos ' .
                           'where id < %d and cam_name = "%s" ' .
                           'order by id desc limit 1',
                    $this->id, $this->cam->name());

        if ($row <= 0)
            return NULL;

        return new Video_file($this->cam, $row['id'], $row['fname'],
                              $row['time'], $row['file_size'], $row['duration']);
    }

    function next() {
        $row = db()->query('select UNIX_TIMESTAMP(created) as time, id, fname, file_size, duration ' .
                    'from videos ' .
                    'where id > %d and cam_name = "%s" ' .
                    'order by id asc limit 1',
                    $this->id, $this->cam->name());

        if ($row <= 0)
            return NULL;

        return new Video_file($this->cam, $row['id'], $row['fname'],
                              $row['time'], $row['file_size'], $row['duration']);
    }

    function url() {
        return mk_url(['mod' => 'videos',
                       'cam' => $this->cam->name(),
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

    function duration() {
        return $this->duration;
    }
}


class Timelapse_file {
    function __construct($cam, $id, $interval_name, $fname, $video_duration,
                         $start_time, $end_time,
                         $progress_duration, $file_size) {
        $this->cam = $cam;
        $this->id = $id;
        $this->interval_name = $interval_name;
        $this->fname = $fname;
        $this->video_duration = $video_duration;
        $this->start_time = $start_time;
        $this->end_time = $end_time;
        $this->progress_duration = $progress_duration;
        $this->file_size = $file_size;
    }

    function interval_str() {
        switch ($this->interval_name) {
        case 'day': return 'дневной';
        case 'week': return 'недельный';
        case 'month': return 'месячный';
        case 'year': return 'годовой';
        }
    }

    function fname() {
        return $this->fname;
    }

    function date_start() {
        return $this->start_time;
    }

    function date_end() {
        return $this->end_time;
    }

    function progress_duration() {
        return $this->progress_duration;
    }

    function size() {
        return $this->file_size;
    }

    function video_duration() {
        return $this->video_duration;
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
        $private = isset($args['private']) ? true : false;

        if ($private)
            $_SESSION["private"] = true;

        if (isset($_SESSION["private"]) and $_SESSION["private"])
            $private = true;


        $tpl = new strontium_tpl("private/tpl/mod_videos.html", conf()['global_marks']);
        $tpl->assign(NULL);

        foreach (conf_dvr()['cameras'] as $info) {
            if (!$info['recording'])
                continue;

            if ($info['private'] and !$private)
                continue;

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
            if ($video)
              $time_position = $video->time();
        }

        $tpl->assign('selector',
                    ['date_position' => $this->timestamp_to_date($time_position),
                     'now_url' => mk_url(['cam' => $cam->name()]),
                    ]);

        $video = $cam->video_by_timestamp($time_position);
        if (!$video) {
            $tpl->assign('no_video');
        } else {
            $tpl->assign('video',
                        ['file' => $video->fname(),
                         'offset' => ($time_position - $video->time()),
                         'size' => sprintf("%.1f", $video->size() / (1024*1024)),
                         'duration' => $video->duration(),
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

        $events = $this->list_guard_events();
        if (is_array($events)) {
            $tpl->assign('event_list');
            foreach ($events as $ev) {
                $tpl->assign('event_item',
                             ['type' => $ev['state'],
                              'date' => $this->timestamp_to_date($ev['time']),
                              'link' => mk_url(['cam' => $cam->name(),
                                                'time_position' => $ev['time']])]);
            }
        }


        $timelapses = $cam->timelapses_by_timestamp($time_position);
        if ($timelapses) {
            $tpl->assign('timelapses');
            foreach ($timelapses as $timelapse) {
                $tpl->assign('timelapse',
                             ['interval' => $timelapse->interval_str(),
                              'date_start' => $this->timestamp_to_date($timelapse->date_start()),
                              'date_end' => $this->timestamp_to_date($timelapse->date_end()),
                              'progress_duration' => $timelapse->progress_duration(),
                              'file' => $timelapse->fname(),
                              'size' => sprintf("%.1f", $timelapse->size() / (1024*1024)),
                              'video_duration' => $timelapse->video_duration()]);
            }
        }


        return $tpl->result();
    }



    function start_rec_time()
    {
        $row = db()->query('select UNIX_TIMESTAMP(created) as time from videos ' .
                           'order by id asc limit 1');
        if (!$row or !isset($row['time']))
            return NULL;
        return $row['time'];
    }

    function list_guard_events() {
        $rows = db()->query_list('select UNIX_TIMESTAMP(created) as time, state, method ' .
                                 'from guard_states ' .
                                 'where UNIX_TIMESTAMP(created) >= %d '.
                                 'order by id desc',
                                 $this->start_rec_time());
        if (!$rows)
            return NULL;
        return $rows;
    }

    function list_guard_alarms() {
        $rows = db()->query_list('select UNIX_TIMESTAMP(created) as time, zone, id ' .
                                 'from guard_alarms ' .
                                 'where UNIX_TIMESTAMP(created) >= %d '.
                                 'order by id desc',
                                 $this->start_rec_time());
        if (!$rows)
            return NULL;
        return $rows;
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

