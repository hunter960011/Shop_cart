<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ShippingMethod
 * @package App
 *
 * @property int $id
 * @property bool|int $enable
 * @property bool $priority
 * @property string $label
 * @property int $rate
 * @property string $time
 * @property string $class_name
 * @property bool|int $selected
 *
 * @method ShippingMethod findOrFail(int $id)
 */
class ShippingMethod extends Model
{
    use HasFactory;
    
    private const NAMESPACE = 'App\\Services\\Shipping\\';
    protected $fillable = array('class_name', 'enable', 'priority');
    
    /**
     * @return Collection
     */
    public function getAllEnabled(): Collection
    {
        $shippingMethods = $this->where('enable', 1)->orderBy('priority')->get();
        
        return $this->addImplementation($shippingMethods);
    }
    
    public static function getLabel(string $className): string
    {
        $class = self::NAMESPACE . $className;
        throw_if(!class_exists($class));
        
        return $class::getLabel();
    }
    
    /**
     * @param int $id
     * @return bool
     */
    public function getStatusById(int $id): bool
    {
        /** @var self $item */
        $item = $this->findOrFail($id);
        return (bool)$item->enable;
    }
    
    /**
     * @param int $id
     * @param bool $status
     */
    public function setStatusById(int $id, bool $status): void
    {
        $this->where('id', $id)->update(['enable' => $status]);
    }
    
    /**
     * @return Collection
     */
    public function list(): Collection
    {
        $shippingMethods = $this->orderBy('priority')->get();
        
        return $this->addImplementation($shippingMethods);
    }
    
    /**
     * @param Collection<ShippingMethod> $shippingMethods
     * @return Collection
     */
    private function addImplementation(Collection $shippingMethods): Collection
    {
        if ($shippingMethods->isNotEmpty()) {
            
            $shippingMethods->filter(static function($shippingMethod, int $idx): bool {
                /**  @var ShippingMethod $shippingMethod */
                return class_exists(self::NAMESPACE . $shippingMethod->class_name);
            });
            
            $shippingMethods->map(static function($shippingMethod) {
                /** @var ShippingMethod $shippingMethod */
                $shippingMethod->class_name = self::NAMESPACE . $shippingMethod->class_name;
                $shippingMethod->label = $shippingMethod->class_name::getLabel();
                $shippingMethod->rate = $shippingMethod->class_name::getRate();
                $shippingMethod->time = $shippingMethod->class_name::getDeliveryTime();
            });
        }
        
        return $shippingMethods;
    }
}
