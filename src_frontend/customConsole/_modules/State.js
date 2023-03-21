/**
 * State.js
 */
module.exports = function(options){
    options = options || {};
    const onSetState = options.onSetState || function(){};
    let state = options.initialState || {};

    this.setState = function(newState){
        state = {
            ...state,
            ...newState,
        };
        onSetState();
    }
    this.getState = function(key){
        return state[key];
    }
};
