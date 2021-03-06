<?php


namespace XsKit\ScoutElasticSearch\Console;


use XsKit\ScoutElasticSearch\Console\Features\RequiresModelArgument;
use XsKit\ScoutElasticSearch\ElasticClient;
use XsKit\ScoutElasticSearch\Payloads\TypePayload;
use XsKit\Traits\ElasticSearch\Migratable;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

class ElasticUpdateMappingCommand extends HyperfCommand
{
    use RequiresModelArgument;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('elastic:update-mapping');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Update a model mapping');
    }

    /**
     *
     * @throws \Exception
     */
    public function handle()
    {
        if (!$model = $this->getModel()) {
            return;
        }

        $configurator = $model->getIndexConfigurator();

        $mapping = array_merge_recursive(
            $configurator->getDefaultMapping(),
            $model->getMapping()
        );

        if (empty($mapping)) {
            throw new \LogicException('Nothing to update: the mapping is not specified.');
        }

        $payload = (new TypePayload($model))
            ->set('body.' . $model->searchableAs(), $mapping)
            ->set('include_type_name', 'true');

        if (in_array(Migratable::class, class_uses_recursive($configurator))) {
            $payload->useAlias('write');
        }

        ElasticClient::indices()
            ->putMapping($payload->get());

        $this->info(sprintf(
            'The %s mapping was updated!',
            $model->searchableAs()
        ));
    }
}