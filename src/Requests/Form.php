<?php

namespace Foundry\Requests;

use Foundry\Requests\Types\FormView;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

abstract class Form
{
	/**
	 * @var array form inputs
	 */
    protected $inputs = [];

	/**
	 * @var Response The response received after calling the hanlde method
	 */
    protected $response;

	/**
	 * @var \Closure The on success handler
	 */
    protected $onSuccess;

	/**
	 * @var \Closure The on error handler
	 */
	protected $onError;

	/**
	 * @var string The view for rendering the form
	 */
	protected $view;

	/**
	 * @var string The action url to submit to
	 */
	protected $action;

	/**
	 * @var string POST, GET, PUT, DELETE
	 */
	protected $method;

	/**
	 * @var Model The associated model instance
	 */
	protected $model;

	/**
	 * @var array The loaded rules
	 */
	protected $rules;

	public function __construct($inputs)
    {
        $this->setInputs($inputs);
        $this->rules = static::rules();
    }

	/**
	 * Set the inputs
	 *
	 * @param $inputs
	 *
	 * @return $this
	 */
    public function setInputs($inputs)
    {
    	$this->inputs = $inputs;
    	return $this;
    }

	/**
	 * Get the inputs
	 *
	 * @return array
	 */
    public function getInputs()
    {
    	return $this->inputs;
    }

	/**
	 * @param $request
	 *
	 * @return Form
	 */
    static public function fromRequest($request)
    {
    	$form = new static($request->only(static::fields()));
    	$form->setRequest($request);
    	return $form;
    }

	/**
	 * Set the request
	 *
	 * @param $request
	 *
	 * @return $this
	 */
    public function setRequest($request)
    {
    	$this->request = $request;
	    return $this;
    }

	/**
	 * Set the rules
	 *
	 * @param $rules
	 */
    public function setRules($rules)
    {
    	$this->rules = $rules;
    }

	/**
	 * Get the set rules
	 *
	 * @return array
	 */
    public function getRules()
    {
    	return $this->rules;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * These are the initial default rules
     *
     * Call getRules and setRules to modify when and if needed
     *
     * @return array
     */
    static abstract function rules();

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public abstract function authorize();

    /**
     * Get available fields based on the permissions of the currently logged in user.
     *
     * @return array
     */
    static abstract function fields();

    /**
     * Get custom error messages for rules
     *
     * @return array
     */
    public abstract function messages();

	/**
	 * Gets the form view object for rendering the form
	 *
	 * @return FormView
	 */
    static abstract function getFormView(): FormView;

	/**
	 * Set the model to use with this form Form Request
	 */
    public function setModel(Model $model): Form
    {
    	$this->model = $model;
    	return $this;
    }

	/**
	 * Get the associated model
	 *
	 * @return mixed
	 */
    public function getModel()
    {
    	return $this->model;
    }

    /**
     * Get values provided by user
     * Validate the values first before returning
     *
     * @return Response
     */
    public function validate($rules = null)
    {
    	if ($rules === null) {
		    $rules = $this->getRules();
	    }
        if($this->authorize()){
            $validator = Validator::make($this->inputs, $rules, $this->messages());
            if ($validator->fails()) {
                return Response::error($validator->errors()->getMessages(), 422);
            }else{
                return Response::success($this->inputs);
            }
        }else{
            return Response::error(__("You are not authorized to view the requested data"), 403);
        }
    }

	/**
	 * Extract only the wanted keys from the input
	 *
	 * @param $keys
	 *
	 * @return array
	 */
    public function only($keys)
    {
	    $results = [];

	    $placeholder = new \stdClass();

	    foreach (is_array($keys) ? $keys : func_get_args() as $key) {
		    $value = data_get($this->inputs, $key, $placeholder);

		    if ($value !== $placeholder) {
			    Arr::set($results, $key, $value);
		    }
	    }

	    return $results;
    }

	/**
	 * Extract only the wanted keys from the input
	 *
	 * @param $keys
	 *
	 * @return array
	 */
	public function except($keys)
	{
		return array_diff_key($this->inputs, array_flip($keys));
	}

	/**
	 * Handle the form using the given service
	 *
	 * @param string $service The Service class name to the use
	 * @param string $method The static method to call against the service class
	 *
	 * @return mixed|Response|View
	 */
    public function handle($service, $method, ...$params)
    {
	    $this->handleBeforeHandle();

	    $this->setResponse(call_user_func([$service, $method], $this, ...$params));

    	if ($this->response->isSuccess()) {
    		return $this->handleOnSuccess();
	    } else {
    		return $this->handleOnError();
	    }
    }

	/**
	 * Set the onBeforeHandle closure
	 *
	 * @param \Closure $closure
	 *
	 * @return Form
	 */
    public function onBeforeHandle(\Closure $closure) : Form
    {
	    $this->onBeforeHandle = $closure;
	    return $this;
    }

	/**
	 * Call the onBeforeHandle
	 *
	 * @return Response|mixed
	 */
    protected function handleBeforeHandle()
    {
	    if (isset($this->onBeforeHandle) && is_callable($this->onBeforeHandle)) {
		    return call_user_func($this->onBeforeHandle, $this);
	    } else {
		    return true;
	    }
    }

	/**
	 * Set the foundry response object
	 *
	 * @param Response $response
	 *
	 * @return $this
	 */
    public function setResponse($response)
    {
    	$this->response = $response;
    	return $this;
    }

	/**
	 * Get the foundry response object
	 *
	 * @return Response
	 */
    public function getResponse()
    {
    	return $this->response;
    }

	/**
	 * Set the view for rendering this form
	 *
	 * @param $view
	 *
	 * @return Form
	 */
    public function setView($view) : Form
    {
    	$this->view = $view;
    	return $this;
    }

	/**
	 * Set the on Success handler
	 *
	 * @param \Closure $closure
	 *
	 * @return Form
	 */
    public function onSuccess(\Closure $closure) : Form
    {
    	$this->onSuccess = $closure;
	    return $this;
    }

	/**
	 * @return mixed|Response|View
	 */
	protected function handleOnSuccess()
    {
    	if (isset($this->onSuccess) && is_callable($this->onSuccess)) {
    		return call_user_func($this->onSuccess, $this);
	    } else {
    		return $this->response;
	    }
    }

	/**
	 * Set the on Error handling
	 *
	 * @param \Closure $closure
	 *
	 * @return Form
	 */
	public function onError(\Closure $closure) : Form
	{
		$this->onError = $closure;
		return $this;
	}

	/**
	 * Handle On Error
	 *
	 * @return mixed|Response|View
	 */
	protected function handleOnError()
	{
		if (isset($this->onError) && is_callable($this->onError)) {
			$return = call_user_func($this->onError, $this);
			if ($return instanceof RedirectResponse) {
				$error = $this->response->getError();
				if (is_string($error)) {
					$return->with('status', $error);
				} elseif (is_array($error)) {
					$return->withErrors($error, 'form');
				}
			}
			return $return;
		} elseif (!empty($this->view)) {
			$form = $this->getFormView();
			$form
				->setAction($this->action)
				->setMethod($this->method)
			;
			$error = $this->response->getError();
			if ($this->response->getCode() === 422) {
				$form->setErrors(new MessageBag($error));
			} elseif(is_string($error)) {
				app('session')->flash('status', $error);
			}
			return view($this->view, [
				'form' => $form
			]);
		} else {
			return $this->response;
		}
	}

	/**
	 * Set the form action
	 *
	 * @param $action
	 *
	 * @return $this
	 */
	public function setAction($action)
	{
		$this->action = $action;
		return $this;
	}

	/**
	 * Set the method of the form
	 *
	 * @param $method
	 *
	 * @return $this
	 */
	public function setMethod($method)
	{
		$this->method = $method;
		return $this;
	}

}
