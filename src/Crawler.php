<?php

namespace phpcrawler;

class Crawler
{
    private $base_url;
    private $config = [
        'clear_dir' => true,
        'dir' => './web/',
        'cookie' => false,
        'parse_ignore_ext' => ['js', 'jpeg', 'jpg', 'gif', 'png'],
        'log_level' => 1,
        'debug' => false,
    ];
    function __construct($base_url, $config = []){
        $this->base_url = $base_url;
        $this->config = array_merge($this->config, $config);
        Logger::level($this->config['log_level']);
    }

    public function crawl($loop = true){
        if($this->config['clear_dir']){
            if(file_exists($this->config['dir'])){
                $res = @deldir($this->config['dir']);
                if(!$res)
                    throw new \Exception('clear directory:"'.$this->config['dir'].'" failed');
            }
        }
        if(!file_exists($this->config['dir'])){
            $res = @mkdir($this->config['dir'], 0755);
            if(!$res)
                throw new \Exception('create directory:"'.$this->config['dir'].'" failed');
        }


        $seed = TaskFactory::init($this->base_url, $this->config);
        $queue = TaskQueue::getInstance();
        $queue->push($seed);

        $loop_count = 0;
        while($task = $queue->shift()){

            Logger::info('开始任务: '.$task['path']);
            $task->download();
            // break;

            if((gettype($loop) == 'boolean' && !$loop) 
                || (is_numeric($loop) && $loop < $loop_count++) 
                || (in_array($task['ext'], $this->config['parse_ignore_ext'])))
                continue;

            $urls = $task->parse();

            foreach($urls as $url){
                $new = TaskFactory::create($url, $task);
                if($new)
                    $queue->push($new);
            }

        }
    }

}
