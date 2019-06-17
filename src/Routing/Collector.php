<?php declare(strict_types=1);

namespace ReactiveApps\Command\HttpServer\Routing;

use Cake\Collection\Collection;
use Doctrine\Common\Annotations\AnnotationReader;
use ReactiveApps\Command\HttpServer\Annotations\Method;
use ReactiveApps\Command\HttpServer\Annotations\Routes;
use ReactiveApps\Command\HttpServer\Annotations\Template;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use function WyriHaximus\from_get_in_packages_composer;
use function WyriHaximus\iteratorOrArrayToArray;
use function WyriHaximus\toChildProcessOrNotToChildProcess;
use function WyriHaximus\toCoroutineOrNotToCoroutine;
use function WyriHaximus\toThreadOrNotToThread;

/**
 * @internal
 */
final class Collector
{
    public static function collect(): iterable
    {
        return self::locateRoutes(iteratorOrArrayToArray(from_get_in_packages_composer('extra.reactive-apps.http-controller')));
    }

    private static function locateRoutes(iterable $controllers): iterable
    {
        foreach ($controllers as $controller) {
            yield from self::locateRoute($controller);
        }
    }

    private static function locateRoute(string $controller): iterable
    {
        if (\strpos($controller, '*') !== false) {
            /** @var iterable $files */
            $files = \glob($controller);
            yield from self::locateRoutes($files);

            return;
        }

        yield from self::controllerRoutes($controller);
    }

    private static function controllerRoutes(string $controller): iterable
    {
        $annotationReader = new AnnotationReader();
        $betterReflection = new BetterReflection();
        $astLocator = $betterReflection->astLocator();
        $reflector = new ClassReflector(new SingleFileSourceLocator($controller, $astLocator));
        foreach ($reflector->getAllClasses() as $class) {
            foreach ($class->getMethods() as $method) {
                $annotations = (new  Collection($annotationReader->getMethodAnnotations((new \ReflectionClass($class->getName()))->getMethod($method->getShortName()))))
                    ->indexBy(function (object $annotation) {
                        return \get_class($annotation);
                    })->toArray();

                if (!isset($annotations[Method::class]) || !isset($annotations[Routes::class])) {
                    continue;
                }

                $requestHandler = $class->getName() . '::' . $method->getName();

                yield [
                    'handler' => $requestHandler,
                    'static' => $method->isStatic(),
                    'routes' => $annotations[Routes::class]->getRoutes(),
                    'method' => $annotations[Method::class]->getMethod(),
                    'template' => isset($annotations[Template::class]) ? $annotations[Template::class]->getTemplate() : false,
                    'annotations' => [
                        'childprocess' => toChildProcessOrNotToChildProcess($requestHandler, $annotationReader),
                        'coroutine' => toCoroutineOrNotToCoroutine($requestHandler, $annotationReader),
                        'thread' => toThreadOrNotToThread($requestHandler, $annotationReader),
                    ],
                ];
            }
        }
    }
}
