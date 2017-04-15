<?php
namespace falkirks\minereset;


use falkirks\minereset\mine\Mine;
use falkirks\minereset\store\DataStore;
use falkirks\minereset\store\Reloadable;
use falkirks\minereset\store\Saveable;
use pocketmine\utils\TextFormat;

class MineManager implements \ArrayAccess, \IteratorAggregate{
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
    private $warps;
    private $flag;

    public function __construct(MineReset $api, DataStore $store, $flag = MineManager::MEMORY_TILL_CLOSE){
        $this->api = $api;
        $this->store = $store;
        $this->flag = $flag;
        $this->warps = [];
        if($this->flag < 2){
            $this->warps = $this->loadWarps();
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
    protected function loadWarps(): array{
        $out = [];
        foreach($this->store->getIterator() as $name => $data){
            $out[$name] = $this->warpFromData($name, $data);
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
            foreach($this->warps as $warp){
                $this->store->add($warp->getName(), $this->warpToData($warp));
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
        if(isset($this->warps[$offset]) || ($this->flag >= 2 && $this->store->exists($offset))){
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
            return $this->warpFromData($offset, $this->store->get($offset));
        }
        return isset($this->warps[$offset]) ? $this->warps[$offset] : null;
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
                $this->warps[$offset] = $value;
            }
            if ($this->flag >= 1) {
                $this->store->add($offset, $this->warpToData($value));
                $this->saveStore();
            }
        }
        else{
            //TODO report failure
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
            unset($this->warps[$offset]);
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
    protected function warpFromData($name, array $array){
        if(isset($array["level"]) && isset($array["x"]) && isset($array["y"]) && isset($array["z"]) && isset($array["public"])){ // This is an internal warp
            return new Warp($this, $name, new Destination(new WeakPosition($array["x"], $array["y"], $array["z"], $array["level"])), $array["public"], $array["metadata"] ?? []);
        }
        elseif(isset($array["address"]) && isset($array["port"]) && isset($array["public"])) {
            return new Warp($this, $name, new Destination($array["address"], $array["port"]), $array["public"], $array["metadata"] ?? []);
        }
        $this->api->getLogger()->critical("A mine with the name " . TextFormat::AQUA . $name . TextFormat::RESET . " is incomplete. It will be removed automatically when your server stops.");
        return null;
    }
    /**
     * In order to pass data to a DataStore
     * a key is needed. Typically one should
     * use $warp->getName()
     * @param Warp $warp
     * @return array
     */
    protected function warpToData(Warp $warp){
        $ret = [];
        if($warp->getDestination()->isInternal()) {
            //TODO implement yaw and pitch
            $pos = $warp->getDestination()->getPosition();
            $ret = [
                "x" => $pos->getX(),
                "y" => $pos->getY(),
                "z" => $pos->getZ(),
                "level" => ($pos instanceof WeakPosition ? $pos->getLevelName() : $pos->getLevel()->getName()),
                "public" => $warp->isPublic(),
            ];
        }
        else{
            $ret = [
                "address" => $warp->getDestination()->getAddress(),
                "port" => $warp->getDestination()->getPort(),
                "public" => $warp->isPublic()
            ];
        }
        $ret["metadata"] = $warp->getAllMetadata();
        return $ret;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator(){
        if($this->flag >= 2){
            return $this->loadWarps();
        }
        return $this->warps;
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
     * ! This will inject your code into SimpleWarp, potentially breaking!
     * @param DataStore $store
     */
    public function setStore(DataStore $store){
        $this->saveAll();
        $this->store = $store;
        if($this->flag < 2){
            $this->warps = $this->loadWarps();
        }
    }

}