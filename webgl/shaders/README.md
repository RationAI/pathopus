# Dynamic shader building

The visualisation setup is used to instantiate to JavaScript shader layer classes.
````JSON
[{    
      "name": "A visualisation setup 1",
      "params": {}, 
      "shaderSources" : [
            {
                   "url": "http://my-shader-url.com/customShader.js",
                   "headers": {},
                   "typedef": "new_type"
            }
      ],
      "shaders": {
            "path/to/probability/layer.tif": {
                   "name": "Probability layer",
                   "type": "heatmap", 
                   "visible": "1", 
                   "params": { 
                      "color": "#fa0058"
                   }
            }
      }
}]
````

Each shader (layer) must inherit from `VisualisationLayer` class. There are pre-defined shader layers such as `identity`, 
`color`, `edge`, `dual-color`. Then, the `shaders[<id>].params` field is sent to the constructor as a single object.

`shaderSources` are used to download and initialize custom-defined shader layers. The output of the specified url must be a text interpretable by JavaScript.
Furthermore:
- shader layer class must inherit from `VisualisationLayer`
    - your constructor must pass the received object to the super constructor
- shader layer class must implement `name()`, `type()` and `getFragmentShaderExecution()` methods
- the class must be registered to `ShaderMediator`
    - e.g. `ShaderMediator.registerLayer(MyNewShaderLayer);`
    - `MyNewShaderLayer` class must not collide with existing classes in the global namespace
    - `type()` return value can overwrite already existing implementations (e.g. `heatmap`), the latter registered class is used
    - `type` in `shaders` `JSON` must refer to existing registered shader classes only (registered under `type()` return value)


### class `VisualisationLayer`
There are several features available for you: things that will make your coding easier. Basic `identity` shader can look
really simple!

````js
class IdentityVisualisationLayer extends VisualisationLayer {

    constructor(options) {
        super();
    }

    getFragmentShaderDefinition() {
        return "";
    }

    getFragmentShaderExecution() {
        return `
        show(${this.sample('tile_texture_coords')});
`;
    }
}
````

#### Writing in GLSL
Your code should reflect these important properties:
- VERSION INDEPENDENCE: the code should be independent of the GLSL version used.
    - `this.webglContext.getVersion()` will tell you the version of WebGL used
- SHADER RE-USABILITY: all global variable and function names must be extended with unique ID so that multiple code 
insertion is possible
    - `this.uid` contains unique identifier for each layer
- COMPLY TO OUT ADJUSTMENTS
    - specify code to define in `getFragmentShaderDefinition`
    - specify code to execute in `getFragmentShaderExecution` (possibly use stuff from the former)
    - output final color using `void show( vec4 )` function

At your disposal are these global variables:
- `uniform float pixel_size_in_fragments;` - how many fragmens can fit into one pixel on the screen
- `uniform float zoom_level;` - zoom level, a value passed from the outer scope, can be anything, in our context used as OpenSeadragon zoom level TODO rename?
- `uniform vec2 u_tile_size;` - size of the canvas
- `in vec2 tile_texture_coords;` - texture coordinates for this fragment

And a member function to sample a texture appropriately:
- [JavaSciript] `this.sample(str)` where str is the string representing the sample coordinates, for example in WebGL 2 the result of
 `this.sample('coords.xy')` could be something like `texture(internally_defined_texarray_name, coords.xy)`

#### Writing the Layer Class
You of course might want to do more such as passing user input into the shader. The `VisualisationLayer` enables you to implement
these member functions:
- `glLoaded(program, gl)` is called when the WebGL program starts to be used, get your uniform locations here (and possibly send time-independent values)
- `glDrawing(program, dimension, gl)` is called when the WebGl drawing event begins, meant to send time-varying uniform values
- `init()` is called always before `glLoaded()`, and after your HTML was attached to the page you can for example initialize your HTML control inputs
- `htmlControls()` is called to generate the shader HTML controls, these are **_replaced_** every time the visualisation is
re-compiled

There are helper functions available so that it is easier to create more advanced, controllable shaders. You can
expect the object of the constructor to contain anything you wish to receive (e.g. flags, default values...). You can
rely on the following functions:
- parsing (not only the input) data
    - `isFlag(value)` - check if value in the input parameters can be interpreted as boolean true, default `false`
    - `isFlagOrMissing(value)` - same as above, interpreted as 'true' if missing
    - `toShaderFloatString(value, defaultValue, precisionLen=5)` - convert value (number) to a string representation with given decimal `precisionLen` length,
    some shaders require you to input floats as `1.0`, `1` could be interpreted as integer
        - e.g. `shader += this.toShaderFloatString(myValue, "1.0", 3);`
    - `to[String/RGBColor/RGBShaderColor]From[String/RGBColor/RGBShaderColor]Color(value, defaultValue)` - convert between array and string representation of an RGB color,
    where `value` is of the from-conversion type and `defaultValue` of the to-conversion type 
        - `String` is hexadecimal representation, e.g. `#5500fa` or `ffffff`
        - `RGBColor` is an array of 0-255 integer values, e.g. `[0, 255, 132]`
        - `RGBShaderColor` is an array of 0-1 float values, e.g. `[0, 1.0, 0.518]`
- writing shaders
    - `sample(str)`
- data caching
    - `loadProperty(name, defaultValue)` - remembered or default value is returned
    - `storeProperty(name, value)` - value is propagated to the internal cache and possibly remembered
- pre-defined user control setup (for `init()`)
    - `simpleControlInit(varName, htmlId, defaultValue, postprocess=undefined)`: in many cases the initialization is similar, if you have
    just one simple html control node, this function will
         - initialize `this.varName` by default or cached value 
         - set up html node to reflect the value (found by `id`, its value and `onchange` properties are set)
         - `onchange` will update `this.varName = postprocess(node.value)` and also cache it
    - `twoElementInit(varName, html1Id, html2Id, defaultValue, postprocess=undefined)`: same as the above, but two HTML elements are synchronized


    
    
    
    
    
    
   
 

### `defined.php`
TODO Contains definitions of shader names, filenames, parameter names, parameters-to-HTML-input mapping and short descriptions.

### `[shader_part].php`
Required parameters (`GET` or `POST`) are `index` and `dataId`. Other parameters are voluntary, shader-dependent, except `unique-id` - a value 
that can be passed from outer `params` field. All parameters must use the same protocol transfer type as the parameter `id`.

_Example URL_: https://ip-78-128-251-178.flt.cloud.muni.cz/visualization/release/client/dynamic_shaders/colors.php?index=1&color=#9900fa&dataId=my_data_identifier

Each shader type has a shader part script that generates following JSON-encoded object output with following fields:
- `definition` - define global variables or custom functions in the resulting shader
- `execution` - write shader code that is executed, placed inside `main{}` and can use pre-defined functions or `definition` part
- `html` - html elements that are to be shown in the visualiser, serve for user input
- `js` - `js` script, that helps to send user values from `html` to shader
- `glLoaded` - `js` code executed when WebGL program is loaded, used to register uniform variables
- `glDrawing` - `js` code executed when WebGL program is used, used to set values to uniforms

**OR**

- `error` - error title - user-friendly message
- `desc` - detailed error description

#### Global functions and variables - PHP
There are some necessary things required to allow advanced functionality. Each file, after `init.php` inclusion, can use global parameters:
- `$uniqueID` - a variable to avoid namespace collision
- `$data` - an array that contains sent parameters
- `$texture_name` - name of the texture that holds data to the current shader part, with respect to the WebGL version used
- function `$texture($sampling_coords, $id=-1)` - use this to sample the texture at `$sampling_coords`, alternatively set custon `$id` to touch data of other shaders (see at the bottom of this README)
- function `send($definition, $execution, $htmlPart="", $jsPart="", $glLoaded="", $glDrawing="")` for unified output style
- function `toShaderFloatString($value, $default, $precisionLen=5)` - use this function to covert a number `$value` to a string with decimal length of `$precisionLen`
- function `toRGBColorFromString($toParse, $default)` - use this function to parse hexadecimal color representation (e.g. `#ffffff`) to an integer array [r, g, b].
More detailed information can be found in the documentation of `init.php`.

#### Global functions and variables - GLSL
In fragment shader (`$execution` and `$definition`), there are several global functions and variables available. Example of really simple _identity_ shader part:

`````php
/**
 * Identity shader
 */
require_once("init.php");

$samplerName = "tile_data_{$uniqueId}";

$definition = <<<EOF
uniform sampler2D $samplerName;
EOF;

//second shader part, if sampled grayscale value is significant, and above threshold, 
//output the color with threshold opacity decreased intentsity
$execution = <<<EOF
  show(texture2D($samplerName, tile_texture_coords));
EOF;

//gl-loaded: what happens when gl program is loaded? define uniform variables
//available variables: gl - webGL context, program - current compiled gl program in use 
$glload = ""; //nothing
//gl-drawing: what happens when gl draw event is invoked? send non-constant values to GPU
//available variables: gl - webGL context, e - current OSD Tile object
$gldraw = ""; //nothing
//html part: controls rendered under shader settings, allows user to change shader uniform values
$html = ""; //nothing
//js part: controls action: update controls if necessary and invoke `redraw();`
$js = ""; //nothing
//print output, it is also possible to call send($definition, $samplerName, $execution); only
send($definition, $samplerName, $execution, $html, $js, $glload, $gldraw);
`````
Shader in WebGL 2.0 is then composed in this manner: (you can see the **global** stuff here)
````glsl
#version 300 es
precision mediump float;
uniform vec2 u_tile_size;                       //tile dimension
uniform float pixel_size_in_fragments;          //how many fragments add up to one pixel on screen
uniform float zoom_level;                       //zoom amount (see OpenSeadragon.Viewport::getZoom())
in vec2 tile_texture_coords;                    //in-texture position
uniform sampler2DArray vis_data_sampler_array;  //texture array with data

out vec4 final_color;      //do not touch directly, fragment output, use show(...) instead

//instead of equality comparison that is unusable on float values
bool close(float value, float target) {
    return abs(target - value) < 0.001;
}

//output any color using show(...) that provides correct blending
void show(vec4 color) {
    if (close(color.a, 0.0)) return;
    float t = color.a + final_color.a - color.a*final_color.a;
    final_color = vec4((color.rgb * color.a + final_color.rgb * final_color.a - final_color.rgb * (final_color.a * color.a)) / t, t);
}

//here is placed any code from $definition part
${definition}

void main() {
    final_color = vec4(0.0, 0.0, 0.0, 0.0);

    //here is placed any code from execution part
    ${execution}
};
````

You can see which global variables and functions are available. The resulting color from `execution` must be set using
`show(...)`. TODO possible boost: The performance can be enhanced in reverse-order rendering if the first `show(...)` call uses alpha
of `1`, the rest of the shader execution can be aborted. This is visualisator-independent and now considered pointless.

#### ~~Global~~ Local ~~functions and~~ variables only - JavaScript inside GLDrawing/GLLoaded
Of course you can use global variables here too (especially the ones defined in pure `js` part), but these two evens are placed inside two functions that
provide you with two local variables:
- `glLoaded` is passed two parameters: `(program, gl)` : `program` is the current program in use, `gl` is the instance of WebGL, so you can call stuff like `my_var_location = gl.getUniformLocation(program, 'nameOfUniformInShader');`
- `glDrawing` is passed two parameters: `(gl, e)` : `gl` is the instance of WebGL, `e` is the current drawing event object (see OSD API, contains for example `tiledImage` property - a reference to the corresponding TiledImage object instance), so you can call stuff like `gl.uniform1f(my_var_location, value);`
 

#### Global functions and variables - JavaScript
For javascript, you can use `redraw();` - will trigger update to the whole canvas, WebGL will be used to update it.

What you __should__ use is variables caching. This is done by touching the internals of the parameter `visualisation`
which is used to set up the whole app. If you use PHP, include `init.php`, otherwise have a look how this is implemented. In PHP,
you can use two functions that will take care of this:
- `$getJSProperty($name, $defaultValue)` will return javascript code (without ending semicolon), where `$name` will be used to search for the variable in the cache, and `$defaultValue` will be returned if no data is found
    - e.g.`<<<EOF let opacity_{$uniqueId} = {$getJSProperty('opacity', 1)}; EOF;` we initialize on global scope variable, so we use `$uniqueId`, 
    and we set its value from the cache (or 1 if not present), note that `opacity` is simple without any uniqueness
- `$setJSProperty($name, $value)` will return javascript code (without ending semicolon), where `$name` will be used to name
the variable in the cache, and `$value` will be stored
    - e.g. `<<<EOF {$setJSProperty('text', '"Some Sentence"')}; EOF;`, where we set `cache.text = "Some Sentence"`, note that
    the value must be enclosed as string, since it is not a variable, this is equivalent to `<<<EOF let text = "Some Sentence"; {$setJSProperty('text', 'text')}; EOF;`, 
    you must undestand that the first colon `'` is for PHP, and the internal value is in fact the JS value.
    
In both cases, this PHP sniplet will return JavaScript code that will correctly work with the cache. Saving and retriving data is important for between-visualisation switching. When your visualisation is loaded (not necessarily for the first time), all your `js` code is
executed. That means the user would lose all presets from the visualisation use history. Here you can nicely cache your variable values so that all changes will be preserved.
Also, you will want to probably propagate these values to various `HTML` input elements you've defined in `$html` part.

### Example of sending user input values to the GPU
We will define an input for user to be able to control a shader uniform variable.
```HTML
<span> Value to send to shader:</span>
<input type="number" id="my-input-type-number-for-this-variable" onchange="myAwesomeOnChangeHandler(this);" min="0" max="100" step="1">
<br>
```
Then, we will add some `JavaScript` code to add logic around the input.
```
//load cached value or default value of 48
var myUniqueNameForVariable = loadCache("myUniqueNameForVariable", 48);

//update HTML input to reflect current state
$("#my-input-type-number-for-this-variable").val(myUniqueNameForVariable);

//called onChange
function myAwesomeOnChangeHandler(self) {
   //get the user input
   myUniqueNameForVariable = $(self).val();
   //save the new value
   saveCache("threshold_{$uniqueId}", threshold_{$uniqueId});
   _this.invalidate();
}

//we will want to later send the variable to shader, it is done by a location parameter
var myUniqueNameForVariableLocationWebGL = null;
```
And finally, we can use `glLoaded` and `glDrawing` to send the user input to the GPU

```
myUniqueNameForVariableLocationWebGL = gl.getUniformLocation(program, 'theNameForUniformWeUsedInTheShaderDefinitionPart');
```
```
gl.uniform1f(myUniqueNameForVariableLocationWebGL, myUniqueNameForVariable);
```


For more complex examples, see scripts themselves. **Non-unique names of variables and functions may cause the shader compilation failure or other
namespace collision.** 
We recommend to extend each custom variable and function name with `$uniqueId`, both for `GLSL` and `JavaScript` parts - of course after you include `init.php`.




### More advanced stuff: using multiple data sources at once
##### **NOTE**: this is an idea of what can be further introduced, it is not implemented (yet)!

One might want to combine multiple data into one visualisation (shader) part. To do so:
- Check the shader source code what indices the shader accesses (the shader might read at index+1 or index-1 or any other value, dependes on the developer!)
    - in case you are wriging the shader yourself: use `$texture($texCoordsString, $dataIndex)` `PHP` function to access arbitrary data, e.g. use `$dataIndex=$index+$i` where `$i` is offset, `$index` is current index: this way we can say 'use data of the following layers: although object, the `shaders` field will keep the order of definition, see the example below'
- Construct the visualisation so that the order of rendering is such that the additional data is at the index position where the shader part accesses it
    - `type` should be `"none"` because we won't render this data, of course you can use any other type to render it as well (e.g. use it twice)
    - `target` must refer to any other `shaders` child that does not have its type `"none"`
Following the example above:
```json
     "shaders": {
         "data_source_main": {
             "name": "Shader that uses multiple data",
             "type": "sophisticated_shader", 
             "visible": "1", 
             "params": { 
                  //your params
             }
         },
         "data_source_additional_1": {
             "type": "none", //tell the visualisation not to touch this data
             "target": "data_source_main" //bind this to the 'data_source_main' visualisation style
             //as an exception, you can ommit other parameters here
         }
     }
```