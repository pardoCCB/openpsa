<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\bundle\test;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use midcom_services_cache;
use Symfony\Component\DependencyInjection\Reference;
use midcom\bundle\dependencyInjection\cachePass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class cachePassTest extends TestCase
{
    public function test_process_nap_apcu()
    {
        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container
            ->expects($this->exactly(4))
            ->method('getParameter')
            ->with($this->logicalOr('midcom.cache_autoload_queue', 'midcom.cache_module_memcache_backend', 'midcom.cache_module_memcache_backend_config', 'kernel.cache_dir'))
            ->will($this->returnCallback([$this, 'get_config']));


        $container
            ->expects($this->exactly(2))
            ->method('getDefinition')
            ->with($this->logicalOr('cache', 'cache.module.nap.backend'))
            ->will($this->returnCallback([$this, 'get_definition_mock']));

        (new cachePass)->process($container);
    }

    public function get_config(string $identifier) {
        if ($identifier === 'kernel.cache_dir') {
            return OPENPSA2_UNITTEST_OUTPUT_DIR . '/cachetest';
        }
        if ($identifier === 'midcom.cache_autoload_queue') {
            return ['nap'];
        }
        if ($identifier === 'midcom.cache_module_memcache_backend') {
            return 'apc';
        }
        return [];
    }

    public function get_definition_mock($identifier)
    {
        if ($identifier == 'cache') {
            $cache = $this
                ->getMockBuilder(Definition::class)
                ->setConstructorArgs([midcom_services_cache::class])
                ->getMock();
            $cache
                ->expects($this->once())
                ->method('addMethodCall')
                ->with('add_module', ['nap', new Reference('cache.module.nap')]);

            return $cache;
        }
        $backend = $this
            ->getMockBuilder(Definition::class)
            ->setConstructorArgs([NullAdapter::class])
            ->getMock();
        $backend
            ->expects($this->once())
            ->method('setArguments')
            ->with(['nap', ApcuAdapter::class, cachePass::NS_PLACEHOLDER]);

        return $backend;
    }

    public function test_process_content_memcached()
    {
        if (!MemcachedAdapter::isSupported()) {
            $this->markTestSkipped('php-memcached missing or version no supported');
        }

        $container = $this->prepare_container();
        $container->register('cache.module.content.backend', NullAdapter::class);
        $container->register('cache.module.content_data.backend', NullAdapter::class);
        $container->setParameter('midcom.cache_autoload_queue', ['content']);
        $container->setParameter('midcom.cache_module_content_backend', ['driver' => 'memcached']);

        (new cachePass)->process($container);

        $this->assertTrue($container->hasDefinition('cache.memcached.content'));
    }

    public function test_process_memcache_flatfile()
    {
        $container = $this->prepare_container();
        $container->setParameter('midcom.cache_autoload_queue', ['memcache']);
        $container->setParameter('midcom.cache_module_memcache_backend', 'flatfile');
        $backend = $container->register('cache.module.memcache.backend', NullAdapter::class);

        (new cachePass)->process($container);

        $this->assertEquals(FilesystemAdapter::class, $backend->getArgument(1));
    }

    public function test_process_memcache_sqlite()
    {
        $container = $this->prepare_container();
        $container->setParameter('midcom.cache_autoload_queue', ['memcache']);
        $container->setParameter('midcom.cache_module_memcache_backend', 'sqlite');
        $backend = $container->register('cache.module.memcache.backend', NullAdapter::class);

        (new cachePass)->process($container);

        $this->assertEquals(PdoAdapter::class, $backend->getArgument(1));
    }

    private function prepare_container() : ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register('cache', midcom_services_cache::class);
        $container->setParameter('kernel.cache_dir', OPENPSA2_UNITTEST_OUTPUT_DIR . '/cachetest');
        $container->setParameter('midcom.cache_module_memcache_backend_config', []);

        return $container;
    }
}
