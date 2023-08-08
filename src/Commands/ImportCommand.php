<?php

namespace tp5er\think\scout\Commands;

use Exception;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Event;
use think\Model;
use tp5er\think\scout\Events\ModelsImported;

/**
 * Class ImportCommand
 * @author zhiqiang
 * @package tp5er\think\scout\Commands
 */
class ImportCommand extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('scout:import')
            ->addArgument('model', Argument::REQUIRED, 'Class name of model to bulk import')
            ->addArgument('chunk', Argument::OPTIONAL, 'The number of records to import at a time', 20)
            ->setDescription('Import the given model into the search index');
    }

    protected function execute(Input $input, Output $output)
    {
        $model = $input->getArgument('model');
        if (!$model instanceof Model && !class_exists($model)) {
            $output->error('Not Find model ' . $model);
            return;
        }
        try {
            ModelsImported::$chunk = (int)$input->getArgument('chunk');
            Event::trigger('onScoutImported', new $model());
            $output->info('All [' . $model . '] records have been imported.');
        } catch (Exception $e) {
            $output->error($e->getMessage());
        }
    }
}