<?php

namespace DaydreamLab\JJAJ\Requests;

use DaydreamLab\JJAJ\Database\QueryCapsule;
use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Traits\ApiJsonResponse;
use DaydreamLab\JJAJ\Traits\CloudflareIp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Str;

class BaseRequest extends FormRequest
{
    use CloudflareIp, ApiJsonResponse;

    protected $apiMethod = null;

    protected $modelName;

    protected $package;

    protected $q;


    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->q = new QueryCapsule();
    }

    public function authorize()
    {
        if (config('app.seeding')) {
            return true;
        } else {
            if (!$this->apiMethod) {
                return true;
            } else {
                $assetId = $this->get('assetId');
                $apis = $this->user()->apis;
                $method = $this->apiMethod;
                if ($this->apiMethod == 'store'. $this->modelName) {
                    $method = $this->get('id')
                        ? 'edit' . $this->modelName
                        : 'add' . $this->modelName;
                }

                return $apis->filter(function ($api) use ($method, $assetId) {
                    return $api->method == $method && $api->assetId == $assetId;
                })->count();
            }
        }
    }


    protected function failedValidation(Validator $validator)
    {
        if (config('app.debug')) {
            throw new HttpResponseException($this->response('InvalidInput', $validator->errors()));
        } else {
            throw new HttpResponseException($this->response('InvalidInput', null));
        }
    }


    public function user($guard = 'api')
    {
        return parent::user($guard);
    }


    public function rules()
    {
        return [
            'assetId' => 'required|integer'
        ];
    }


    public function validated()
    {
        $validated = parent::validated();
        $validated['q'] = $this->q;
        $validated = collect($validated);
        $validated->forget('assetId');

        return $validated;
    }
}
