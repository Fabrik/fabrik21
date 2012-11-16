/**
 * @author Robert
 * 
 * Make fabrik js code compatible with mootools 1.2
 */
// Class.prototype.extend in the compat library doesn't actually work for some reason. So...
// Note that this will hide Function.extend from use in certain cases
Function.prototype.extend = function(properties) {
    if (this.prototype) {
        // Assume its a class
        properties.Extends = this;
        return new Class(properties);
    }        
    for (var property in properties) this[property] = properties[property];
    return this;
};
