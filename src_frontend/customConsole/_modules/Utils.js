/**
 * Utils.js
 */
module.exports = function(){

	this.fixSitemapDefinition = function( blogmapDefinition, sitemapDefinition ){
		blogmapDefinition.forEach((definition, index) => {
			if( sitemapDefinition[definition.key] ){
                blogmapDefinition[index] = {
                    ...blogmapDefinition[index],
                    ...sitemapDefinition[definition.key],
                };
				return;
			}
			if( definition.key.match(/(?:\-|\_)fla?g$/) ){
				blogmapDefinition[index].type = "boolean";
			}else if( definition.key.match(/^is(?:\-|\_)/) ){
				blogmapDefinition[index].type = "boolean";
			}else if( definition.key.match(/(?:\-|\_)date$/) ){
				blogmapDefinition[index].type = "date";
			}else if( definition.key.match(/(?:\-|\_)datetime$/) ){
				blogmapDefinition[index].type = "datetime";
			}
		});
		return blogmapDefinition;
	};

};
