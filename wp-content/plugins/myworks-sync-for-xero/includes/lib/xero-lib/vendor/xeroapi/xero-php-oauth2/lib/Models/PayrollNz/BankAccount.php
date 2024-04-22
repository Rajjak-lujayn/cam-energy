<?php
/**
 * BankAccount
 *
 * PHP version 5
 *
 * @category Class
 * @package  XeroAPI\XeroPHP
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */

/**
 * Xero Payroll NZ
 *
 * This is the Xero Payroll API for orgs in the NZ region.
 *
 * Contact: api@xero.com
 * Generated by: https://openapi-generator.tech
 * OpenAPI Generator version: 5.4.0
 */

/**
 * NOTE: This class is auto generated by OpenAPI Generator (https://openapi-generator.tech).
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */

namespace XeroAPI\XeroPHP\Models\PayrollNz;

use \ArrayAccess;
use \XeroAPI\XeroPHP\PayrollNzObjectSerializer;
use \XeroAPI\XeroPHP\StringUtil;
use ReturnTypeWillChange;

/**
 * BankAccount Class Doc Comment
 *
 * @category Class
 * @package  XeroAPI\XeroPHP
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class BankAccount implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'BankAccount';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'account_name' => 'string',
        'account_number' => 'string',
        'sort_code' => 'string',
        'particulars' => 'string',
        'code' => 'string',
        'dollar_amount' => 'double',
        'reference' => 'string',
        'calculation_type' => 'string'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPIFormats = [
        'account_name' => null,
        'account_number' => null,
        'sort_code' => null,
        'particulars' => null,
        'code' => null,
        'dollar_amount' => 'double',
        'reference' => null,
        'calculation_type' => null
    ];

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPITypes()
    {
        return self::$openAPITypes;
    }

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPIFormats()
    {
        return self::$openAPIFormats;
    }

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @var string[]
     */
    protected static $attributeMap = [
        'account_name' => 'accountName',
        'account_number' => 'accountNumber',
        'sort_code' => 'sortCode',
        'particulars' => 'particulars',
        'code' => 'code',
        'dollar_amount' => 'dollarAmount',
        'reference' => 'reference',
        'calculation_type' => 'calculationType'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'account_name' => 'setAccountName',
        'account_number' => 'setAccountNumber',
        'sort_code' => 'setSortCode',
        'particulars' => 'setParticulars',
        'code' => 'setCode',
        'dollar_amount' => 'setDollarAmount',
        'reference' => 'setReference',
        'calculation_type' => 'setCalculationType'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'account_name' => 'getAccountName',
        'account_number' => 'getAccountNumber',
        'sort_code' => 'getSortCode',
        'particulars' => 'getParticulars',
        'code' => 'getCode',
        'dollar_amount' => 'getDollarAmount',
        'reference' => 'getReference',
        'calculation_type' => 'getCalculationType'
    ];

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @return array
     */
    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @return array
     */
    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @return array
     */
    public static function getters()
    {
        return self::$getters;
    }

    /**
     * The original name of the model.
     *
     * @return string
     */
    public function getModelName()
    {
        return self::$openAPIModelName;
    }

    const CALCULATION_TYPE_FIXED_AMOUNT = 'FixedAmount';
    const CALCULATION_TYPE_BALANCE = 'Balance';
    

    
    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getCalculationTypeAllowableValues()
    {
        return [
            self::CALCULATION_TYPE_FIXED_AMOUNT,
            self::CALCULATION_TYPE_BALANCE,
        ];
    }
    

    /**
     * Associative array for storing property values
     *
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     *
     * @param mixed[] $data Associated array of property values
     *                      initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->container['account_name'] = isset($data['account_name']) ? $data['account_name'] : null;
        $this->container['account_number'] = isset($data['account_number']) ? $data['account_number'] : null;
        $this->container['sort_code'] = isset($data['sort_code']) ? $data['sort_code'] : null;
        $this->container['particulars'] = isset($data['particulars']) ? $data['particulars'] : null;
        $this->container['code'] = isset($data['code']) ? $data['code'] : null;
        $this->container['dollar_amount'] = isset($data['dollar_amount']) ? $data['dollar_amount'] : null;
        $this->container['reference'] = isset($data['reference']) ? $data['reference'] : null;
        $this->container['calculation_type'] = isset($data['calculation_type']) ? $data['calculation_type'] : null;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        if ($this->container['account_name'] === null) {
            $invalidProperties[] = "'account_name' can't be null";
        }
        if ($this->container['account_number'] === null) {
            $invalidProperties[] = "'account_number' can't be null";
        }
        if ($this->container['sort_code'] === null) {
            $invalidProperties[] = "'sort_code' can't be null";
        }
        $allowedValues = $this->getCalculationTypeAllowableValues();
        if (!is_null($this->container['calculation_type']) && !in_array($this->container['calculation_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value for 'calculation_type', must be one of '%s'",
                implode("', '", $allowedValues)
            );
        }

        return $invalidProperties;
    }

    /**
     * Validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }


    /**
     * Gets account_name
     *
     * @return string
     */
    public function getAccountName()
    {
        return $this->container['account_name'];
    }

    /**
     * Sets account_name
     *
     * @param string $account_name Bank account name (max length = 32)
     *
     * @return $this
     */
    public function setAccountName($account_name)
    {

        $this->container['account_name'] = $account_name;

        return $this;
    }



    /**
     * Gets account_number
     *
     * @return string
     */
    public function getAccountNumber()
    {
        return $this->container['account_number'];
    }

    /**
     * Sets account_number
     *
     * @param string $account_number Bank account number (digits only; max length = 8)
     *
     * @return $this
     */
    public function setAccountNumber($account_number)
    {

        $this->container['account_number'] = $account_number;

        return $this;
    }



    /**
     * Gets sort_code
     *
     * @return string
     */
    public function getSortCode()
    {
        return $this->container['sort_code'];
    }

    /**
     * Sets sort_code
     *
     * @param string $sort_code Bank account sort code (6 digits)
     *
     * @return $this
     */
    public function setSortCode($sort_code)
    {

        $this->container['sort_code'] = $sort_code;

        return $this;
    }



    /**
     * Gets particulars
     *
     * @return string|null
     */
    public function getParticulars()
    {
        return $this->container['particulars'];
    }

    /**
     * Sets particulars
     *
     * @param string|null $particulars Particulars that appear on the statement.
     *
     * @return $this
     */
    public function setParticulars($particulars)
    {

        $this->container['particulars'] = $particulars;

        return $this;
    }



    /**
     * Gets code
     *
     * @return string|null
     */
    public function getCode()
    {
        return $this->container['code'];
    }

    /**
     * Sets code
     *
     * @param string|null $code Code of a transaction that appear on the statement.
     *
     * @return $this
     */
    public function setCode($code)
    {

        $this->container['code'] = $code;

        return $this;
    }



    /**
     * Gets dollar_amount
     *
     * @return double|null
     */
    public function getDollarAmount()
    {
        return $this->container['dollar_amount'];
    }

    /**
     * Sets dollar_amount
     *
     * @param double|null $dollar_amount Dollar amount of a transaction.
     *
     * @return $this
     */
    public function setDollarAmount($dollar_amount)
    {

        $this->container['dollar_amount'] = $dollar_amount;

        return $this;
    }



    /**
     * Gets reference
     *
     * @return string|null
     */
    public function getReference()
    {
        return $this->container['reference'];
    }

    /**
     * Sets reference
     *
     * @param string|null $reference Statement Text/reference for a transaction that appear on the statement.
     *
     * @return $this
     */
    public function setReference($reference)
    {

        $this->container['reference'] = $reference;

        return $this;
    }



    /**
     * Gets calculation_type
     *
     * @return string|null
     */
    public function getCalculationType()
    {
        return $this->container['calculation_type'];
    }

    /**
     * Sets calculation_type
     *
     * @param string|null $calculation_type Calculation type for the transaction can be 'Fixed Amount' or 'Balance'
     *
     * @return $this
     */
    public function setCalculationType($calculation_type)
    {
        $allowedValues = $this->getCalculationTypeAllowableValues();
        if (!is_null($calculation_type) && !in_array($calculation_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value for 'calculation_type', must be one of '%s'",
                    implode("', '", $allowedValues)
                )
            );
        }

        $this->container['calculation_type'] = $calculation_type;

        return $this;
    }


    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer $offset Offset
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     *
     * @param integer $offset Offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     *
     * @param integer $offset Offset
     * @param mixed   $value  Value to be set
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     *
     * @param integer $offset Offset
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Gets the string presentation of the object
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode(
            PayrollNzObjectSerializer::sanitizeForSerialization($this),
            JSON_PRETTY_PRINT
        );
    }
}


