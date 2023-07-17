=== Soft Media Delete ===

Contributors: Faheem Ahmad  

== Description ==

Allows uploading images for categories, prevent directly delete images on media library and display IDs if image attached with post or category.
Also implementation of REST API under `/assignment/v1/` namespace to get the info of image and delete the image through Image ID.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `soft-media-delete.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Media library and check images
4. If image associate with any categories or post then it return ID of it and it 
prevent the deletion of images if assigned.

== REST API  ==

You can check the API functionality to get the details of image thorugh image ID
Create GET request through POSTMAN and use following url
For Local Environment(Localhost):
GET: http://{{domain-name}}/{{project-directory}}/wp-json/assignment/v1/images/{{image-id}}
like if you test this API on localhost of wampserver then url is like
GET: http://localhost/{{project-directory}}/wp-json/assignment/v1/images/15  // suppose 15 is image id
For Live Domain Environment:
GET: http://{{domain-name}}/wp-json/assignment/v1/images/{{image-id}}


You can check the API functionality to delete the image thorugh image ID
Create DELETE request through POSTMAN and use following url
DELETE: http://{{domain-name}}/{{project-directory}}/wp-json/assignment/v1/delete-image/{{image-id}}
like if you test this API on localhost of wampserver then url is like
DELETE: http://localhost/{{project-directory}}/wp-json/assignment/v1/delete-image/15  // suppose 15 is image id
For Live Domain Environment:
DELETE: http://{{domain-name}}/wp-json/assignment/v1/delete-image/{{image-id}}

= Unit Test Cases =

Following cases are performed
1. It add the upload image field and preview on add or edit category page where user can upload, remove and update the image.
2. It prevent the user to delete the image in grid and list view of media library if image is associate as a featured image, content body and category.
3. In list view when try to delte the image it display the alert with associate id's list and prevent the image cannot be delete.
4. If image is not associate with any post or categories then it will delete.
5. In grid view of media library when click on image then open attachment details popup of image, below it shows categories id and posts id in text field with edit link.
6. In list view of media library add custom column Attached objects and it shows the list of id's with edit link if image is associate with categories and posts.
7. In Detail image API endpoint if entered correct id of image then it return the expected details of image in response.
8. If enter invalid image id then it return error "invalid image id".
10.In Delte image API endpoint if entered correct id and if image is associate with any categories or posts then it display error image is attached with post or categories.
11.If enter invalid image id then it return error "Image not found: Invalid image ID"
12.If enter correct image id and if is is not associate with any image then image delte sucessfully.
13.Also it return null value of some fields if data is empty of that image field.
14.Loop is implemented to handle the huge amount of data and display the multiple id's if image assigned.
15.API end plugin tested completly for huge data too. 
16.Commit added in functions against each function functionality

= Version 1.0 =
