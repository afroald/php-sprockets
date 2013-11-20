#php-sprockets
A port (kind of) of the [Sprockets](https://github.com/sstephenson/sprockets) gem. This is a work in progress.

It functions the same as Sprockets, except that this isn't able to serve the assets.

## It goes something like this
Set-up the asset pipeline.

	$config = array(
		'debug' => false,
		'cache_path' => null,

		'compress_js' => false,
		'compress_css' => false,

		'filters' => array(
			'coffeescript' => array(
				'bare' => false
			),
			'sass' => array(
				'compass' => false
			)
		)
	);
	
	$loadPaths = glob('assets/*');
	
    $pipeline = new Sprockets\Pipeline($loadPaths, $config);

Now you can find assets in the load paths.

    $asset = $pipeline->asset('app');

You can access the processed (and compressed) contents.

    echo $asset->content;

## Directives
The directives implemented so far:

- `require`
- `require_self`