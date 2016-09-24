<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 16.07.2016
 */
namespace v3toys\skeeks\components;
use skeeks\cms\base\Component;

use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\CmsContent;
use skeeks\cms\shop\models\ShopCmsContentElement;
use skeeks\cms\shop\models\ShopOrderStatus;
use skeeks\cms\shop\models\ShopPersonType;
use skeeks\widget\chosen\Chosen;
use v3toys\skeeks\helpers\ShippingHelper;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;

/**
 * @property ShopPersonType $shopPersonType
 *
 * @property [] $currentShippingData
 * @property [] $outletsData
 * @property StringHelper $currentShipping
 *
 * Class V3toysSettings
 * @package v3toys\skeeks\components
 */
class V3toysSettings extends Component
{
    /**
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name'          => \Yii::t('v3toys/skeeks', 'Настройки v3toys'),
        ]);
    }

    /**
     * @var string
     */
    public $v3toysIdPropertyName = 'vendorId';

    /**
     * @var int
     */
    public $v3toysShopPersonTypeId;

    /**
     * @var string
     */
    public $affiliate_key;

    /**
     * @var array Контент свяазанный с v3project
     */
    public $content_ids = [];

    /**
     * @var string Статус заказа, когда он отправлен в Submitted
     */
    public $v3toysOrderStatusSubmitted;



    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            ['v3toysIdPropertyName', 'string'],
            ['content_ids', 'safe'],
            ['v3toysShopPersonTypeId', 'integer'],
            ['affiliate_key', 'string'],
            ['v3toysOrderStatusSubmitted', 'string'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'v3toysIdPropertyName'          => 'Название параметра у товаров — v3toys id',
            'content_ids'                   => 'Контент свяазанный с v3project',
            'v3toysShopPersonTypeId'        => 'Профиль покупателя v3project',
            'affiliate_key'                 => 'Код аффилиата полученный в v3project',
            'v3toysOrderStatusSubmitted'    => 'Статус заказа, когда он отправлен в Submitted',
        ]);
    }

    public function attributeHints()
    {
        $link = urlencode(Url::base(true));
        $a = Html::a('http://www.seogadget.ru/ip?urls=' . $link, 'http://www.seogadget.ru/ip?urls=' . $link, ['target' => '_blank']);

        return ArrayHelper::merge(parent::attributeHints(), [
            'v3toysIdPropertyName'      => 'Как называется свойство товаров, в котором храниться id товара из системы v3toys',
            'content_ids'               => 'Обновление наличия и цен будет происходить у элементов этого выбранного контента',
            'v3toysShopPersonTypeId'    => 'Необходимо настроить тип покупателя, и его свойства, для связи с данными v3toys [ <b>php yii v3toys/init/update-person-type</b> ]',
            'affiliate_key'             => 'Ключ связан с ip адресом сайта, необходимо сообщить свой IP. Проверить IP можно тут: ' . $a,
        ]);
    }

    public function renderConfigForm(ActiveForm $form)
    {
        echo $form->fieldSet('Общие настройки');
            echo $form->field($this, 'affiliate_key');
            echo $form->field($this, 'v3toysIdPropertyName');
            echo $form->field($this, 'content_ids')->widget(Chosen::className(),[
                'multiple' => true,
                'items' => CmsContent::getDataForSelect(),
            ]);
            echo $form->field($this, 'v3toysShopPersonTypeId')->widget(Chosen::className(),[
                'items' => ArrayHelper::map(ShopPersonType::find()->all(), 'id', 'name'),
            ]);
            echo $form->field($this, 'v3toysOrderStatusSubmitted')->widget(Chosen::className(),[
                'items' => ArrayHelper::map(ShopOrderStatus::find()->all(), 'code', 'name'),
            ]);
        echo $form->fieldSetEnd();
    }

    /**
     * @return ShopPersonType
     */
    public function getShopPersonType()
    {
        return ShopPersonType::findOne((int) $this->v3toysShopPersonTypeId);
    }












        /**
         * Новое api
         */


    private $_shipping = null;

    /**
     * Удобрый объект с информацией о текущей доставке.
     *
     * @return StringHelper
     */
    public function getCurrentShipping()
    {
        if ($this->_shipping !== null)
        {
            return $this->_shipping;
        }

        $this->_shipping = new ShippingHelper([
            'apiData' => $this->currentShippingData
        ]);

        return $this->_shipping;
    }

    /**
     * Данные по текущей доставке
     *
     * @return array
     */
    public function getCurrentShippingData()
    {
        return $this->getShippingData();
    }

    /**
     *
     * Получение данных по доставке + кэширование данных
     *
     * @param array $geoobject                              @see schema
     * @param int $max_distance_from_outlet_to_geobject     Удаленность от геообъекта
     * @return array|mixed
     */
    public function getShippingData($geoobject = [], $max_distance_from_outlet_to_geobject = 50)
    {
        if (!$geoobject)
        {
            if (\Yii::$app->dadataSuggest->address)
            {
                $geoobject = \Yii::$app->dadataSuggest->address->toArray();
            }
        }
        if (!$geoobject)
        {
            return [];
        }

        $cacheKey = md5(serialize($geoobject) . $max_distance_from_outlet_to_geobject);

        if (!$data = \Yii::$app->cache->get($cacheKey))
        {
            $response = \Yii::$app->v3projectApi->orderGetGuidingShippingData([
                'geobject' => $geoobject,
                'order' => [
                    'products' => [
                        [
                            'v3p_product_id' => 176837,
                            'quantity' => 1,
                            'realize_price' => 438,
                        ]
                    ],
                ],
                'filters' => [
                    'max_distance_from_outlet_to_geobject' => $max_distance_from_outlet_to_geobject
                ]
            ]);

            if ($response->isOk)
            {
                $data = $response->data;
                \Yii::$app->cache->set($cacheKey, $data);
            }
        }

        return $data;
    }

    /**
     * @return int
     */
    public function getCurrentMinPickupPrice()
    {
        $minPrice = 0;

        if ($outlets = ArrayHelper::getValue($this->currentShippingData, 'pickup.outlets'))
        {
            foreach ($outlets as $outletData)
            {
                if ($outletPrice = ArrayHelper::getValue($outletData, 'guiding_realize_price'))
                {
                    $minPrice = $outletPrice;
                }
            }
        }

        return $minPrice;
    }







    /**
     * @param ShopCmsContentElement $shopCmsContentElement
     */
    /*public function getShippingDataByProduct(ShopCmsContentElement $shopCmsContentElement)
    {
        if (!\Yii::$app->dadataSuggest->address)
        {
            return [];
        }

        $response = \Yii::$app->v3projectApi->orderGetGuidingShippingData([
            'geobject' => \Yii::$app->dadataSuggest->address->data,
            'order' => [
                'products' => [
                    [
                        'v3p_product_id' => $shopCmsContentElement->relatedPropertiesModel->getAttribute($this->v3toysIdPropertyName),
                        'quantity' => 1,
                        'realize_price' => $shopCmsContentElement->shopProduct->baseProductPrice->money->getValue(),
                    ]
                ],
            ],
            'filters' => [
                'max_distance_from_outlet_to_geobject' => 20
            ]
        ]);

        if ($response->isOk)
        {
            print_r($response->data);
        }
    }*/
}
