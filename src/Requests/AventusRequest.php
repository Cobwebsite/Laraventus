<?php

namespace Aventus\Laraventus\Requests;

use Aventus\Laraventus\Attributes\ArrayOf;
use Aventus\Laraventus\Attributes\NoExport;
use Aventus\Laraventus\Models\AventusFile;
use Aventus\Laraventus\Models\AventusModel;
use Aventus\Laraventus\Requests\Rules\Boolean;
use Aventus\Laraventus\Tools\Console;
use Aventus\Laraventus\Tools\Json;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

class AventusRequest extends FormRequest
{

    private $aventusRules = [];
    private $classToCreate = [];
    private $arrayClassToCreate = [];
    private $arrays = [];
    private $enums = [];
    private $filesName = [];

    protected bool $useAutomaticRules = true;

    public function __construct(Request $request)
    {
        $this->addAventusRules();
        // Console::dump($request->request->all());
        parent::__construct(
            $request->query->all(), // GET parameters
            $request->request->all(), // POST parameters
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all()
        );
    }

    /**
     * Handle a passed validation attempt.
     *
     * @return void
     */
    protected function passedValidation()
    {
        $this->bindProperties();
    }

    /**
     * Get the validation rules for this form request.
     *
     * @return array
     */
    protected function validationRules()
    {
        $rules = method_exists($this, 'rules') ? $this->container->call([$this, 'rules']) : [];
        if (!$this->useAutomaticRules) {
            return $rules;
        }
        $result = $this->aventusRules;
        foreach ($rules as $key => $_rules) {
            if (array_key_exists($key, $result)) {
                if (is_array($_rules)) {
                    foreach ($_rules as $rule) {
                        if (!in_array($rule, $result[$key])) {
                            $result[$key][] = $rule;
                        }
                    }
                } else {
                    if (!in_array($_rules, $result[$key])) {
                        $result[$key][] = $_rules;
                    }
                }
            } else {
                $result[$key] = $_rules;
            }
        }
        return $result;
    }

    private function addAventusRules()
    {
        $reflection = new ReflectionClass(get_called_class());
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $reflection = new ReflectionClass(self::class);
        $propertiesTemp = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $prevents = [];
        foreach ($propertiesTemp as $property) {
            $prevents[] = $property->getName();
        }

        foreach ($properties as $property) {
            $name = $property->getName();
            if (in_array($name, $prevents))
                continue;

            if (count($property->getAttributes(NoExport::class)) > 0) {
                continue;
            }

            $type = $property->getType();
            $types = [];
            $isNullable = false;
            if ($type instanceof ReflectionNamedType) {
                $types[] = $type->getName();
                $isNullable = $type->allowsNull();
            } elseif ($type instanceof ReflectionUnionType) {
                foreach ($type->getTypes() as $subType) {
                    $types[] = $subType->getName();
                    if ($subType->allowsNull()) {
                        $isNullable = true;
                    }
                }
            }

            if ($property->getDefaultValue()) {
                $isNullable = true;
            }


            if (count($types) == 1) {
                $type = $types[0];
                if ($type == "int") {
                    $this->addAventusRule($name, "integer");
                } else if ($type == "DateTime") {
                    $this->addAventusRule($name, "date");
                } else if ($type == "float") {
                    $this->addAventusRule($name, "numeric");
                } else if ($type == "string") {
                    $this->addAventusRule($name, "string");
                } else if ($type == "bool") {
                    $this->addAventusRule($name, new Boolean);
                } else if ($type == "array") {
                    $this->addAventusRule($name, "array");
                    if (!$isNullable) {
                        $isNullable = true;
                        $this->arrays[] = $name;
                    }
                    $attributes = $property->getAttributes(ArrayOf::class);
                    if (count($attributes) > 0) {
                        $this->arrayClassToCreate[$name] = $attributes[0]->newInstance()->class;
                    }
                } else if (is_a($type, AventusFile::class, true)) {
                    $this->addAventusRule($name, "array");
                    $this->filesName[] = $name;
                    $this->classToCreate[$name] = $type;
                } else if (is_a($type, UploadedFile::class, true)) {
                    $this->filesName[] = $name;
                    $this->addAventusRule($name, "file");
                } else if (enum_exists($type)) {
                    $this->addAventusRule($name, Rule::enum($type));
                    $this->enums[$name] = $type;
                } else if (class_exists($type)) {
                    $this->addAventusRule($name, "array");
                    $this->classToCreate[$name] = $type;
                } else {
                    Console::dump($type);
                    die();
                }
            }

            if (!$isNullable) {
                $this->addAventusRule($name, "required");
            } else {
                $this->addAventusRule($name, "nullable");
            }
        }
    }

    private function addAventusRule($name, $rule)
    {
        if (!array_key_exists($name, $this->aventusRules)) {
            $this->aventusRules[$name] = [];
        }
        if (!in_array($rule, $this->aventusRules[$name])) {
            $this->aventusRules[$name][] = $rule;
        }
    }

    private function bindProperties()
    {
        AventusModel::$debug = true;
        $reflection = new ReflectionClass(get_called_class());
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $reflection = new ReflectionClass(self::class);
        $propertiesTemp = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $prevents = [];
        foreach ($propertiesTemp as $property) {
            $prevents[] = $property->getName();
        }

        foreach ($properties as $property) {
            $name = $property->getName();
            if (in_array($name, $prevents))
                continue;

            if (count($property->getAttributes(NoExport::class)) > 0) {
                continue;
            }

            $valueTemp = Json::toClassObj($this->input($name));
            if (isset($this->$name) && $valueTemp == null) {
                continue;
            }

            if (array_key_exists($name, $this->arrayClassToCreate)) {
                $defaultValues = is_array($valueTemp) ? $valueTemp : [];
                $this->$name = [];
                if (is_a($this->arrayClassToCreate[$name], Model::class, true)) {
                    foreach ($defaultValues as $value) {
                        if (is_object($value) && is_a(get_class($value), $this->arrayClassToCreate[$name], true)) {
                            $this->$name[] = $value;
                        } else {
                            $this->$name[] = new $this->arrayClassToCreate[$name]((array)$value);
                        }
                    }
                } else {
                    foreach ($defaultValues as $value) {
                        $newItem = new $this->arrayClassToCreate[$name]();
                        foreach ($value as $key => $v) {
                            $newItem->{$key} = $v;
                        }
                        $this->$name[] = $newItem;
                    }
                }
            } else if (array_key_exists($name, $this->classToCreate)) {
                if (is_object($valueTemp) && is_a(get_class($valueTemp), $this->classToCreate[$name], true)) {
                    $this->{$name} = $valueTemp;
                } else {
                    $defaultValues = is_array($valueTemp) ? $valueTemp : [];
                    if (in_array($name, $this->filesName)) {
                        $defaultValues["upload"] = $this->file($name . ".upload");
                    }
                    if (is_a($this->classToCreate[$name], Model::class, true)) {
                        $this->{$name} = new $this->classToCreate[$name]($defaultValues);
                    } else {
                        $this->{$name} = new $this->classToCreate[$name]();
                        foreach ($defaultValues as $key => $value) {
                            $this->{$name}->{$key} = $value;
                        }
                    }
                }
            } else if (array_key_exists($name, $this->enums)) {
                if ($valueTemp != null) {
                    $valueTemp = $this->enums[$name]::from($valueTemp);
                }
                $this->$name = $valueTemp;
            } else if (in_array($name, $this->filesName)) {
                $this->{$name} = $this->file($name);
            } else if (in_array($name, $this->arrays)) {
                $this->{$name} = $valueTemp == null ? [] : $valueTemp;
            } else {
                $this->{$name} = $valueTemp;
            }
        }
        AventusModel::$debug = false;
    }

    /**
     * @template U
     * @param class-string<U> $model
     * @return U
     */
    public function toModel(string $model)
    {
        $reflection = new ReflectionClass(get_called_class());
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $reflection = new ReflectionClass(self::class);
        $propertiesTemp = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $prevents = [];
        foreach ($propertiesTemp as $property) {
            $prevents[] = $property->getName();
        }

        $reflection = new ReflectionClass($model);
        $_methods = $reflection->getMethods(ReflectionProperty::IS_PUBLIC);
        $methods = [];

        foreach ($_methods as $method) {
            $returnType = $method->getReturnType();
            if ($returnType instanceof ReflectionNamedType) {
                if ($returnType->getName() == HasOne::class) {
                    $name = $method->getName();
                    $methods[$name] = "HasOne";
                } else if ($returnType->getName() == HasMany::class) {
                    $name = $method->getName();
                    $methods[$name] = "HasMany";
                } else if ($returnType->getName() == BelongsTo::class) {
                    $name = $method->getName();
                    $methods[$name] = "BelongsTo";
                } else if ($returnType->getName() == BelongsToMany::class) {
                    $name = $method->getName();
                    $methods[$name] = "BelongsToMany";
                }
            }
        }

        $data = [];
        $links = [];
        $saveLinks = $this->save_links();
        foreach ($properties as $property) {
            $name = $property->getName();
            if (in_array($name, $prevents))
                continue;

            $data[$name] = $this->$name;

            if ($saveLinks == null && array_key_exists($name, $methods)) {
                $links[$name] = $methods[$name];
            }
        }

        /** @var AventusModel $result */
        $result = new $model($data);

        if ($saveLinks != null) {
            $result->saveLinks = [];
            foreach ($saveLinks as $saveLink) {
                $parts = explode(".", $saveLink);

                $loop = function ($obj, $parts) use (&$loop) {
                    if (count($parts) == 0) return;
                    $part = $parts[0];
                    if ($obj instanceof AventusModel) {
                        if ($obj->isRelation($part)) {
                            $reflection = new ReflectionClass(get_class($obj));
                            $method = $reflection->getMethod($part);
                            $returnType = $method->getReturnType();
                            if ($returnType instanceof ReflectionNamedType) {
                                $default = null;
                                if ($returnType->getName() == HasOne::class) {
                                    $obj->saveLinks[$part] = "HasOne";
                                } else if ($returnType->getName() == HasMany::class) {
                                    $obj->saveLinks[$part] = "HasMany";
                                    $default = new Collection();
                                } else if ($returnType->getName() == BelongsTo::class) {
                                    $obj->saveLinks[$part] = "BelongsTo";
                                } else if ($returnType->getName() == BelongsToMany::class) {
                                    $obj->saveLinks[$part] = "BelongsToMany";
                                    $default = new Collection();
                                }

                                if (!$obj->relationLoaded($part)) {
                                    $obj->setRelation($part, $default);
                                }

                                $value = $obj->{$part};

                                array_shift($parts);
                                if ($value instanceof Collection) {
                                    foreach ($value as $item) {
                                        $loop($item, $parts);
                                    }
                                } else {
                                    $loop($value, $parts);
                                }
                            }
                        }
                    }
                };

                $loop($result, $parts);
            }
        } else {
            $result->saveLinks = $links;
        }




        if ($result->only_fillable) {
            foreach ($data as $key => $value) {
                if ($result->isRelation($key)) {
                    if (is_array($value)) {
                        $value = new Collection($value);
                    }
                    $result->setRelation($key, $value);
                }
            }
        }

        // define sub link

        return $result;
    }

    /**
     * List relations to save
     * @return null|string[]
     */
    protected function save_links(): null|array
    {
        return null;
    }

    // /**
    //  * @template U
    //  * @param class-string<U> $model
    //  * @return U[]
    //  */
    // public function toModels(string $model)
    // {
    //     $list = $this->post();
    //     $list = is_array($list) ? $list : [];
    //     $result = [];
    //     foreach ($list as $defaultValues) {
    //         $result[] = new $model($defaultValues);
    //     }
    //     return $result;
    // }
}
