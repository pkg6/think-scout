<?php

namespace tp5er\think\scout\Commands;


use Exception;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Event;
use think\Model;
use tp5er\think\scout\Events\CreateIndex;

/**
 * Class IndexCommand
 * @author zhiqiang
 * @package tp5er\think\scout\Commands
 */
class IndexCommand extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('scout:index')
            ->addArgument('model', Argument::REQUIRED, 'model class')
            ->addArgument('key')
            ->setDescription('Create an index');
    }

    protected function execute(Input $input, Output $output)
    {
        $model = $input->getArgument('model');
        if (!$model instanceof Model && !class_exists($model)) {
            $output->error('Not Find model ' . $model);
            return;
        }
        try {
            $options = [];
            if ($key = $input->getArgument('key')) {
                $options['primaryKey'] = $key;
            }
            CreateIndex::$options = $options;
            Event::trigger('onScoutCreateIndex', new $model());
            $output->info('Index ["' . $model . '"] created successfully.');
        } catch (Exception $e) {
            $output->error($e->getMessage());
        }
    }
}