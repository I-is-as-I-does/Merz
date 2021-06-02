
# Merz

Create, merge, minify and move around files, folders or assets with Merz.

This component is a work in progress.

## Config

Structure:

```JSON
{
    "jobs": {
      "your-job-id": {
        "method": "someMerzMethod",
        "param": {
          "firstParam": [
              "..."
            ],
          "anotherParam":"...",
          "profile":"{profile}"
        }
      }
    }
  }
```

## Merzbau

```PHP
use SSITU\Merz\Merzbau;

require_once 'path/to/vendor/autoload.php';

$configPath = 'path/to/merz-config.json';
$jobId = "your-job-id";
$profile = "some-profile-id";
$merz = new Merzbau($configPath);
$do = $merz->runJob($jobId, $profile);
```

'Profiles' are a way to run a job with some variations.  
`{profile}` in a path, for example, will be replaced by the "profile" argument of `runJob`.

## CLI

To run Merz in CLI, install `ssitu/euclid`, and in *Euclid* config file:

```JSON
{ 
  "maps" : {
       "merz": {
            "className": "SSITU\\Merz\\Merzbau"
        }
   }
}
```

## Contributing

Sure! You can take a loot at [CONTRIBUTING](CONTRIBUTING.md).

## License

This project is under the MIT License; cf. [LICENSE](LICENSE) for details.