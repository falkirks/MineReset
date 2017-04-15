<?php
namespace falkirks\minereset\store;

use pocketmine\utils\Config;

/**
 * Class YAMLStore
 * @package falkirks\minereset\store
 */
class YAMLStore extends AbstractStore implements Saveable, Reloadable{
    /** @var Config  */
    private $config;

    /**
     * YAMLStore constructor.
     * @param Config $config
     */
    public function __construct(Config $config){
        $this->config = $config;
    }

    /**
     * Adds a new mine and returns the old one
     * @param $name
     * @param $warp
     * @return bool|mixed
     */
    public function add($name, $mine){
        $past = $this->config->get($name, null);
        $this->config->set($name, $mine);
        $this->config->save();
        return $past;
    }

    /**
     * Gets mine with $name
     * @param $name
     * @return bool|mixed
     */
    public function get($name){
        return $this->config->get($name, null);
    }

    /**
     * Removes a mine with $name and returns it
     * @param $name
     * @return bool|mixed
     */
    public function remove($name){
        $past = $this->config->get($name, null);
        $this->config->remove($name);
        $this->config->save();
        return $past;
    }

    /**
     * Clears all mines
     */
    public function clear(){
        $this->config->setAll([]);
        $this->config->save();
    }

    /**
     *  Reloads the mines from YAML
     */
    public function reload(){
        $this->config->reload();
    }
    /**
     * Returns something which can be used to iterate
     * over the store.
     * @return mixed
     */
    public function getIterator(){
        return $this->config->getAll();
    }

    /**
     * Saves mines to file
     */
    public function save(){
        $this->config->save();
    }
}