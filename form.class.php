<?php
            class Article {
                /** @var string */
                public $title = '';
                /** @var string */
                public $body = '';
                /** @var DateTime */
                public $date;

                public function __construct() {
                    // this will be explained when we get to re-creating the object
                    // after a POST request
                    $this->date = $this->date ?
                        new DateTime($this->date) :
                        new DateTime();
                }
                /**
                * We want the title and body to be plain text inputs, and the date to be a date input. We are going to handle both the displaying of the form through a GET request,
                * and the submission of the form through a POST request. Let us begin by enumerating our class's properties, creating an input for each one, and also wrapping them
                * in a form tag with a submit input. Let us get straight to reflection. I have only annotated the MakeInput function as the other two should be self-explanatory.

                * This function creates a valid HTML input with an associated label.
                * @param string $name The input's name and the label's text.
                * @param mixed $value The input's initial value. This parameter also determines the input's type.
                * @return string
                */
                public function MakeInput(string $name, $value): string
                {
                    $type = gettype($value);
                    $label = sprintf('<label for="%1$s">%1$s</label>', $name);
                    switch ($type) {
                        case 'boolean':
                            $input_type = 'checkbox';
                            break;
                        case 'integer':
                        case 'double':
                            $input_type = 'number';
                            break;
                        case 'string':
                            $input_type = 'text';
                            break;
                        case 'object':
                            // special handling for object types
                            $class = new ReflectionClass($value);
                            if ($class->implementsInterface(DateTimeInterface::class)) {
                                $input_type = 'date';
                                $value = $class
                                    ->getMethod('format')
                                    ->invoke($value, 'Y-m-d');
                                break;
                            }
                            // if we do not know how to handle the object, fall-through and throw
                        default:
                            throw new InvalidArgumentException($value);
                    }
                    $input = sprintf('<input name="%1$s" id="%1$s" type="%2$s" value="%3$s" />',
                        $name, $input_type, $value);

                    return $label . $input;
                }

                public function MakeInputs(string $class_name): array
                {
                    $inputs = [];
                    $instance = new $class_name();
                    $class = new ReflectionClass($instance);
                    $properties = $class->getProperties();
                    foreach ($properties as $property) {
                        // make it accessible so we can get its value
                        $property->setAccessible(true);
                        $name = $property->getName();
                        $value = $property->getValue($instance);
                        // this syntax means that after submission our $_POST superglobal
                        // will contain an array named $class_name which will represent
                        // our class
                        $inputs[] = self::MakeInput("{$class_name}[{$name}]", $value);
                    }

                    return $inputs;
                }

                public static function MakeForm(string $class_name): string
                {
                    $html = '<form method="POST">';
                    foreach (self::MakeInputs($class_name) as $input)
                        $html .= $input;
                    $html .= '<input type="submit" />';
                    $html .= '</form>';

                    return $html;
                }

                public function MakeClassFromArray(string $class_name, array $values)
                {
                    $class = new ReflectionClass($class_name);
                    // we do not call the constructor yet
                    $instance = $class->newInstanceWithoutConstructor();
                    // first we set each property to their respective value
                    foreach ($values as $name => $value) {
                        $property = $class->getProperty($name);
                        $property->setAccessible(true);
                        $property->setValue($instance, $value);
                    }
                    // note that we have set primitive values to our object properties
                    // we late-call the constructor, like PDO does when fetching objects
                    // and it re-creates the object instances from the primitive values
                    $class->getConstructor()->invoke($instance);

                    return $instance;
                }
            };