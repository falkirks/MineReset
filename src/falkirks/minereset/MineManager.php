<?php
namespace falkirks\minereset;


use falkirks\minereset\store\DataStore;
use falkirks\minereset\store\Reloadable;
use falkirks\minereset\store\Saveable;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class MineManager implements \ArrayAccess, \IteratorAggregate, \Countable {
    const MEMORY_TILL_CLOSE = 0;
    const FLUSH_ON_CHANGE = 1;
    /**
     * This option is pretty scary :(
     */
    const NO_MEMORY_STORE = 2;
    /** @var MineReset  */
    private $api;
    /** @var DataStore  */
    private $store;
    /** @var  Mine[] */
    private $mines;
    private $flag;

    public function __construct(MineReset $api, DataStore $store, $flag = MineManager::FLUSH_ON_CHANGE){
        $this->api = $api;
        $this->store = $store;
        $this->flag = $flag;
        $this->mines = [];
        if($this->flag < 2){
            $this->mines = $this->loadMines();
        }
    }
    protected function reloadStore(){
        if($this->flag >= 2 && $this->store instanceof Reloadable){
            $this->store->reload();
        }
    }
    protected function saveStore($force = false){
        if(($this->flag > 0 || $force) && $this->store instanceof Saveable){
            $this->store->save();
        }
    }
    protected function loadMines(): array{
        $out = [];
        foreach($this->store->getIterator() as $name => $data){
            $out[$name] = $this->mineFromData($name, $data);
        }
        return $out;
    }
    /**
     * WARNING
     * This function is for internal use only.
     */
    public function saveAll(){
        if($this->flag === 0){
            $this->store->clear();
            foreach($this->mines as $mine){
                $this->store->add($mine->getName(), $this->mineToData($mine));
            }
            $this->saveStore(true);
        }
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset){
        $this->reloadStore();
        if(isset($this->mines[$offset]) || ($this->flag >= 2 && $this->store->exists($offset))){
            return true;
        }
        return false;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset){
        if($this->flag >= 2){
            $this->reloadStore();
            return $this->mineFromData($offset, $this->store->get($offset));
        }
        return isset($this->mines[$offset]) ? $this->mines[$offset] : null;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value){
        if($value instanceof Mine && $value->getName() === $offset) {
            if($this->flag < 2) {
                $this->mines[$offset] = $value;
            }
            if ($this->flag >= 1) {
                $this->store->add($offset, $this->mineToData($value));
                $this->saveStore();
            }
        }
        else{
            throw new \RuntimeException("Invalid \$offset for mine data.");
        }
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset){
        if($this->flag < 2){
            unset($this->mines[$offset]);
        }
        if($this->flag >= 1){
            $this->store->remove($offset);
            $this->saveStore();
        }
    }
    /**
     * This method requires the key of the warp in order
     * to construct a warp object
     * @param $name
     * @param array $array
     * @return Mine
     * @throws \Exception
     */
    protected function mineFromData($name, array $array){
        if(count($array) === 8) {
            if(!$this->getApi()->getServer()->isLevelLoaded($array[7])){
                $this->api->getLogger()->warning("A mine with the name " . TextFormat::AQUA . $name . TextFormat::RESET . " is connected to a level which is not loaded. You won't be able to use it until you load the level correctly.");
            }

            if(is_array($array[6])) {
                return new Mine($this,
                    new Vector3(min($array[0], $array[1]), min($array[2], $array[3]), min($array[4], $array[5])),
                    new Vector3(max($array[0], $array[1]), max($array[2], $array[3]), max($array[4], $array[5])),
                    $array[7],
                    $name,
                    $array[6]);
            }
            else{
                return new Mine($this,
                    new Vector3(min($array[0], $array[1]), min($array[2], $array[3]), min($array[4], $array[5])),
                    new Vector3(max($array[0], $array[1]), max($array[2], $array[3]), max($array[4], $array[5])),
                    $array[7],
                    $name);
            }
        }
        $this->api->getLogger()->critical("A mine with the name " . TextFormat::AQUA . $name . TextFormat::RESET . " is incomplete. It will be removed automatically when your server stops.");
        return null;
    }
    /**
     * In order to pass data to a DataStore
     * a key is needed. Typically one should
     * use $warp->getName()
     * @param Mine $mine
     * @return array
     */
    protected function mineToData(Mine $mine){
        return  [
            $mine->getPointA()->getX(),
            $mine->getPointB()->getX(),
            $mine->getPointA()->getY(),
            $mine->getPointB()->getY(),
            $mine->getPointA()->getZ(),
            $mine->getPointB()->getZ(),
            (count($mine->getData()) > 0 ? $mine->getData() : false),
            $mine->getLevelName()
        ];
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator(){
        if($this->flag >= 2){
            return new \ArrayIterator($this->loadMines());
        }
        return new \ArrayIterator($this->mines);
    }

    public function count(){
        return count($this->mines);
    }


    /**
     * Returns the current storage-mode
     * #####
     *  MEMORY_TILL_CLOSE = 0
     * Warps are loaded into memory when the server starts and are
     * held there until the server closes. When the server closes
     * they are converted back into YAML. This new YAML will
     * replace warps.yml, this means that changes are lost and
     * warps which fail to load are discarded.
     *
     *
     * FLUSH_ON_CHANGE = 1
     * Warps are loaded into memory when the server starts. Whenever a
     * warp is updated, it will be updated in the warps.yml. When the
     * server closes, the warps file is NOT overwritten.
     *
     * NO_MEMORY_STORE = 2
     * Warps are never "stored" in memory. They are converted on demand
     * between YAML and object format. Any changes made to the config
     * will be available right away in the server and vice versa.
     * ####
     * @return int
     */
    public function getFlag(): int{
        return $this->flag;
    }
    /**
     * returns the current data store
     * @return DataStore
     */
    public function getStore(): DataStore{
        return $this->store;
    }
    /**
     * Injects a new DataStore for warps
     * ! This will inject your code into MineReset, potentially breaking!
     * @param DataStore $store
     */
    public function setStore(DataStore $store){
        $this->saveAll();
        $this->store = $store;
        if($this->flag < 2){
            $this->mines = $this->loadMines();
        }
    }

    /**
     * @return MineReset
     */
    public function getApi(): MineReset{
        return $this->api;
    }

    /**
     * @return Mine[]
     */
    public function getMines(): array{
        return $this->mines;
    }
}