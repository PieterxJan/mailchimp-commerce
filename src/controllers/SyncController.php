<?php
/**
 * Mailchimp for Craft Commerce
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\mc\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\records\Discount;
use craft\db\Query;
use craft\errors\ElementNotFoundException;
use craft\errors\SiteNotFoundException;
use craft\web\Controller;
use ether\mc\jobs\SyncOrders;
use ether\mc\jobs\SyncProducts;
use ether\mc\jobs\SyncPromos;
use ether\mc\MailchimpCommerce;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Class SyncController
 *
 * @author  Ether Creative
 * @package ether\mc\controllers
 */
class SyncController extends Controller
{

	/**
	 * @throws Throwable
	 * @throws ElementNotFoundException
	 * @throws SiteNotFoundException
	 * @throws Exception
	 * @throws InvalidConfigException
	 */
	public function actionStore ()
	{
		MailchimpCommerce::$i->store->update();

		Craft::$app->getSession()->setNotice(
			MailchimpCommerce::t('Store Synced.')
		);
	}

	public function actionAllProducts ()
	{
		$typeId = Craft::$app->getRequest()->getBodyParam('type');
		$productIdsQuery = (new Query())
			->select('id')
			->from('{{%commerce_products}}');

		if ($typeId)
			$productIdsQuery->where(['typeId' => $typeId]);

		Craft::$app->getQueue()->push(
			new SyncProducts([
				'productIds' => $productIdsQuery->column(),
			])
		);
	}

	public function actionAllCarts ()
	{
		Craft::$app->getQueue()->push(
			new SyncOrders([
				'orderIds' => Order::find()->isCompleted(false)->ids()
			])
		);
	}

	public function actionAllOrders ()
	{
		Craft::$app->getQueue()->push(
			new SyncOrders([
				'orderIds' => Order::find()->isCompleted(true)->ids()
			])
		);
	}

	public function actionAllPromos ()
	{
		Craft::$app->getQueue()->push(
			new SyncPromos([
				'promoIds' => Discount::find()->select('id')->column(),
			])
		);
	}

}