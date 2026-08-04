(function (wp) {
	const { registerBlockType } = wp.blocks;
	const ServerSideRender = wp.serverSideRender;
	const { createElement } = wp.element;

	if (registerBlockType && ServerSideRender) {
		registerBlockType('eka/homepage-services-grid', {
			title: 'Homepage Services Grid',
			icon: 'grid-view',
			category: 'widgets',
			edit: function (props) {
				return createElement(ServerSideRender, {
					block: 'eka/homepage-services-grid',
					attributes: props.attributes
				});
			},
			save: function () {
				return null;
			}
		});
	}
})(window.wp);
