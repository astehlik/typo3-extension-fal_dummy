# Dummy image TYPO3 FAL driver

fal_dummy is a TYPO3 Extension that provides a dummy image driver the TYPO3 File Abstraction Layer (FAL).

## What is it good for?

This driver can be used in development or testing systems where you want to use the contents from the live
system but you do not want to copy all the images.

The driver will use a placeholder service (like http://placehold.it/) to generate images in the expected
sizes.

## How to use it

1. Install the Extension (just clone it, not yet in TER)
2. Configure your preferred placeholder service in the Extension configuration
3. Set the driver of your storage(s) to "Dummy"
4. Clear the sys_file_processedfile and remove all files from the _processed_/ directories.

Now all images that are not available in the storage (normally they would be marked as "missing") will be
replaced with images from the placeholder service.
