<?php

namespace Aventus\Laraventus\Routes;

use Illuminate\Routing\ResourceRegistrar as LaravelResourceRegistrar;

class ResourceRegistrar extends LaravelResourceRegistrar {

    protected $resourceDefaults = ['index', 'storeMany', 'showMany', 'updateMany', 'destroyMany', 'store', 'show', 'update', 'destroy'];


     /**
     * Add the store many method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceStoreMany($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/many';

        unset($options['missing']);

        $action = $this->getResourceAction($name, $controller, 'storeMany', $options);

        return $this->router->post($uri, $action);
    }

     /**
     * Add the show many method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceShowMany($name, $base, $controller, $options)
    {
        $name = $this->getShallowName($name, $options);
        $uri = $this->getResourceUri($name).'/show_many';
        $action = $this->getResourceAction($name, $controller, 'showMany', $options);
        return $this->router->post($uri, $action);
    }

     /**
     * Add the show many method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceUpdateMany($name, $base, $controller, $options)
    {
        $name = $this->getShallowName($name, $options);
        $uri = $this->getResourceUri($name).'/many';
        $action = $this->getResourceAction($name, $controller, 'updateMany', $options);
        return $this->router->match(['PUT', 'PATCH'], $uri, $action);
    }

     /**
     * Add the show many method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceDestroyMany($name, $base, $controller, $options)
    {
        $name = $this->getShallowName($name, $options);
        $uri = $this->getResourceUri($name).'/many';
        $action = $this->getResourceAction($name, $controller, 'destroyMany', $options);
        return $this->router->delete($uri, $action);
    }
}