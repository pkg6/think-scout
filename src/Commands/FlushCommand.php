<?php


namespace tp5er\think\scout\Commands;

use Exception;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Event;
use think\Model;

class FlushCommand extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('scout:flush')
            ->addArgument('model', Argument::REQUIRED, 'model class')
            ->setDescription("Flush all of the model's records from the index");
    }

    protected function execute(Input $input, Output $output)
    {
        $model = $input->getArgument('model');

        if (!$model instanceof Model && !class_exists($model)) {
            $output->error('Not Find model ' . $model);
            return;
        }
        try {
            Event::trigger('onScoutFlushed', new $model());
            $output->info('All [' . $model . '] records have been flushed.');
        } catch (Exception $e) {
            $output->error($e->getMessage());
        }
    }
}