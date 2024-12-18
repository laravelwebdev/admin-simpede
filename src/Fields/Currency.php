<?php

namespace Laravel\Nova\Fields;

use Brick\Money\Context;
use Brick\Money\Context\CustomContext;
use Brick\Money\Money;
use NumberFormatter;
use Symfony\Polyfill\Intl\Icu\Currencies;

/**
 * @property string|null $step
 *
 * @method $this step(string|null $step)
 */
class Currency extends Number
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'currency-field';

    /**
     * The locale of the field.
     *
     * @var string
     */
    public $locale;

    /**
     * The currency of the value.
     *
     * @var string
     */
    public $currency;

    /**
     * The symbol used by the currency.
     *
     * @var string|null
     */
    public $currencySymbol = null;

    /**
     * Whether the currency is using minor units.
     *
     * @var bool
     */
    public $minorUnits = false;

    /**
     * The context to use when creating the Money instance.
     *
     * @var \Brick\Money\Context|null
     */
    public $context = null;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|object|null  $attribute
     * @param  (callable(mixed, mixed, ?string):(mixed))|null  $resolveCallback
     * @return void
     */
    public function __construct($name, mixed $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->locale = config('app.locale', 'en');
        $this->currency = config('nova.currency', 'USD');

        $this->step($this->getStepValue())
            ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                $value = $request->$requestAttribute;

                if ($this->minorUnits && ! $this->isValidNullValue($value)) {
                    $model->$attribute = $this->toMoneyInstance(
                        $value * (10 ** Currencies::getFractionDigits($this->currency)),
                        $this->currency
                    )->getMinorAmount()->toInt();
                } else {
                    $model->$attribute = $value;
                }
            })
            ->displayUsing(function ($value) {
                return ! $this->isValidNullValue($value) ? $this->formatMoney($value) : null;
            })
            ->resolveUsing(function ($value) {
                if ($this->isValidNullValue($value) || ! $this->minorUnits) {
                    return $value;
                }

                return $this->toMoneyInstance($value)->getAmount()->toFloat();
            });
    }

    /**
     * Convert the value to a Money instance.
     */
    public function toMoneyInstance(mixed $value, ?string $currency = null): Money
    {
        $currency ??= $this->currency;
        $method = $this->minorUnits ? 'ofMinor' : 'of';

        $context = $this->context ?? new CustomContext(Currencies::getFractionDigits($currency));

        return Money::{$method}($value, $currency, $context);
    }

    /**
     * Format the field's value into Money format.
     */
    public function formatMoney(mixed $value, ?string $currency = null, ?string $locale = null): string
    {
        $money = $this->toMoneyInstance($value, $currency);

        if (is_null($this->currencySymbol)) {
            return $money->formatTo($locale ?? $this->locale);
        }

        return tap(new NumberFormatter($locale ?? $this->locale, NumberFormatter::CURRENCY), function ($formatter) use ($money) {
            $scale = $money->getAmount()->getScale();

            $formatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, $this->currencySymbol);
            $formatter->setSymbol(NumberFormatter::INTL_CURRENCY_SYMBOL, $this->currencySymbol);
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $scale);
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $scale);
        })->format($money->getAmount()->toFloat());
    }

    /**
     * Set the currency code for the field.
     *
     * @return $this
     */
    public function currency(string $currency)
    {
        $this->currency = strtoupper($currency);

        $this->step($this->getStepValue());

        return $this;
    }

    /**
     * Set the field locale.
     *
     * @return $this
     */
    public function locale(string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set the symbol used by the field.
     *
     * @return $this
     */
    public function symbol(string $symbol)
    {
        $this->currencySymbol = $symbol;

        return $this;
    }

    /**
     * Instruct the field to use minor units.
     *
     * @return $this
     */
    public function asMinorUnits()
    {
        $this->minorUnits = true;

        return $this;
    }

    /**
     * Instruct the field to use major units.
     *
     * @return $this
     */
    public function asMajorUnits()
    {
        $this->minorUnits = false;

        return $this;
    }

    /**
     * Resolve the symbol used by the currency.
     */
    public function resolveCurrencySymbol(): string
    {
        if ($this->currencySymbol) {
            return $this->currencySymbol;
        }

        return Currencies::getSymbol($this->currency);
    }

    /**
     * Set the context used to create the Money instance.
     *
     * @return $this
     */
    public function context(Context $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Check value for null value.
     */
    #[\Override]
    public function isValidNullValue(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        return parent::isValidNullValue($value);
    }

    /**
     * Determine the step value for the field.
     */
    protected function getStepValue(): string
    {
        return (string) 0.1 ** Currencies::getFractionDigits($this->currency);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'currency' => $this->resolveCurrencySymbol(),
        ]);
    }
}
