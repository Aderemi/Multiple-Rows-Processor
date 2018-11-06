<?php
/**
 * Created by PhpStorm.
 * User: Akins
 * Date: 11/4/2018
 * Time: 2:35 PM
 */

namespace MultipleRows\Tests;


trait TestTrait
{
    /**
     * @param string $method
     * @param array $data
     * @return array
     */
    protected function rules(string $method, array $data): array
    {
        switch ($method) {
            case "POST":
                return [
                    "product_sku" => "required|string|unique:" . TestModel::getTableName() . ",product_sku",
                    "name" => "required|string|unique:" . TestModel::getTableName() . ",name",
                    "brand_name" => "required|string",
                    "sub_category" => "string",
                    "category#1" => "string",
                    "category#2" => "string",
                    "price" => "required|numeric|min:0",
                    "discount" => "numeric",
                    "page_title" => "string",
                ];
            case 'PUT':
                return [
                    "product_sku" => "required|string|unique:" . TestModel::getTableName() . ",product_sku",
                    "name" => "filled|string|unique:" . TestModel::getTableName() . ",name",
                    "brand_name" => "filled|string",
                    "sub_category" => "string",
                    "category#1" => "string",
                    "category#2" => "string",
                    "price" => "numeric|min:0",
                    "discount" => "numeric",
                    "page_title" => "string",
                ];
            case 'DELETE':
                return [
                    "product_sku" => "required|string|unique:" . TestModel::getTableName() . ",product_sku",
                    "name" => "required|string|unique:" . TestModel::getTableName() . ",name",
                ];
        }
    }

    /**
     * @return string
     */
    public function getUniqueIDField(): string
    {
        return 'product_sku';
    }

    /**
     * @return Model
     */
    protected function model(): Model
    {
        return new TestModel();
    }

    /**
     * @return EventProcessor
     */
    protected function eventProcessor(): EventProcessor
    {
        return new TestEventProcessor();
    }

    /**
     * Returns array of headers that might be a form of placeholder like
     * ['create' => 'name:required|discount|/^category*#\d$/:match|sub_cat:contain|system:start_with|batch:end_with,required']
     * each column is separated with a |,
     * in the example:
     * name column is required
     * discount column is not
     * the third rule is regex to match headers like category#1, category#2, category#3
     * the fourth rule match column header that contain 'sub_cat' like sub_categories
     * the fifth rule match column header that start with 'system' like system_generated
     * the sixth rule match column header that end with 'batch' like first_batch
     * match a given regex, the header must contain sub_category and so on
     * @return array
     */
    public function getRuleHeaders(): array
    {
        return [
            'create' => 'name:required|product_sku:required|price:required|discount|/^category*#\d$/:match|sub_cat:contain|status:start_with,required|_title:end_with',
            'update' => 'product_sku:required',
            'delete' => 'name:require|product_sku:required',
            'activate' => 'product_sku:required'
        ];
    }

}