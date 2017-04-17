<?php
namespace falkirks\minereset\store;

/**
 * This interface deals with the storage of arbitrary mine
 * data in a key-value store.
 *
 * This is extracted from SimpleWarp and redistributed under the same license
 * as Mine Reset.
 *
 * Interface DataStore
 * @package falkirks\minereset\store
 */
interface DataStore {
    public function addAll($mines);
    public function removeAll($mines);
    public function exists($name) : bool ;
    /**
     * This method takes a $name string and a $warp array and
     * returns the previous value that occupied $name or null.
     * @param $name
     * @param $mine
     * @return mixed
     */
    public function add($name, $mine);
    /**
     * @param $name
     * @return mixed
     */
    public function get($name);
    public function remove($name);
    public function clear();
    /**
     * Returns something which can be used to iterate
     * over the store.
     * @return mixed
     */
    public function getIterator();
}