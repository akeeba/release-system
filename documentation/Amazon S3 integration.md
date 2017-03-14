# Overview

Akeeba Release System fully support Amazon S3 for storing your download items and serving them directly from there. It also supports Bleeding Edge categories hosted on Amazon S3.

We chose to integrate Amazon S3 with ARS because it is the most reputable and very low cost cloud storage provider. Using S3 to store your download items instead of your server's filesystem means that you don't consume your server's bandwidth with your downloads. Instead, everything is diverted to S3 where bandwidth is generally much cheaper than what hosting providers offer. Moreover, your files are always privately stored and can not be accessed by unauthorised users, even if they successfully guess the correct path to the files, something which is much harder to implement on a regular web server.

# Configuring the Amazon S3 integration

Before you can use Amazon S3 with ARS, you have to configure it. Go to the main page (Control Panel) of the component and click on the Options button. You will see the Amazon S3 integration area in the configuration page. The available options are:

**Access Key**  
This is the "Access Key" you have created in your Amazon S3 account page. In order to retrieve it, please go to <http://aws.amazon.com/s3/> and click on Account, Security Credentials. In the Access Credentials area, click on the Access Keys tab. You will see your Access Key ID there. Copy it and paste it in the Access Key field of ARS' configuration.

**Secret Key**  
This is the "Secret Key" which corresponds to your Amazon S3 Access Key. In the same page as the the Access Key, find your Access Key on the list. On its right, you will see a link reading Show. Click on it to reveal your Secret Access Key. Copy it and paste it in the Secret Key configuration field of ARS.

**Bucket**  
This is the name of your Amazon S3 bucket. A "bucket" can be thought of as a virtual drive living in Amazon's cloud where you can store files in. In order to manage your Amazon S3 buckets, please visit <https://console.aws.amazon.com/s3/home>. The list of bucket names appears on the left hand side. If you don't have any buckets yet, click on the Create button to create one.

Click on the bucket you want to use with ARS. It becomes selected and the right-hand pane loads a list of its contents. Above the right-hand pane there is a toolbar. Click on the Properties button. A bottom pane opens. On the let-hand side, you will see the Name field. Copy the name appearing there and paste it to ARS' configuration field.

> **Important**
>
> Bucket names are case sensitive! This means that ABC, abc and Abc are three different bucket names. Be perfectly sure that you copy and paste the bucket name as instructed above to avoid upper/lower-case mismatch which could cause ARS to not be able to use your Amazon S3 bucket.

**Default permissions**  
Amazon S3 has its own set of ACLs for the files created in the S3 buckets. If you are going to be offering private downloads, we urge you to set this option to `Private`. This will cause all files uploaded by ARS to your S3 bucket to be only accessible by your Amazon S3 user (and, since you configured the integration, ARS will be able to access them too). For more information about Amazon S3 ACLs please consult the Amazon S3 documentation.

**Timeout for authenticated URLs**  
Since ARS is designed to work with files stored on Amazon S3 as "Private", there would be an issue: how could we serve those files to the site's visitors? One way would be to download the file to your server, then serve it like the other file downloads. This would make the downloads dead slow! Instead, we use what Amazon calls "authenticated URLs". An authenticated URL is a specially crafted URL which allows anyone knowing it to access and download the private file it references for a limited amount of time. So, ARS creates an authenticated URL and redirects the user's browser to it when he asks to download the file.

This ARS configuration option determines for how long the authenticated URL will be valid. By default, this is 1 minute, which is more than ample time to initiate the download. However, if your client is using a download manager and schedules the download for a later time, the URL will have expired before the download manager tries to access it, causing the download to fail. If you get grumpy users complaining that your downloads don't work, you can increase this timeout. We consider that anything over one day is an overkill, but it's your option to set it to as high as you want, up to a full month.

# Creating categories linked to S3

Any ARS category can be instructed to look inside your configured S3 bucket instead of your server's filesystem. In the Files Directory option of the category you have to enter something like `s3://mydirectory` where `mydirectory` is the name of a directory in your S3 bucket. If you want to use your bucket's root for this category (not recommended!) you can simply enter `s3://` in this field.

From that point, all file operations are performed against the S3 bucket instead of your server's filesystem. In fact, you can use the Upload and Manage Files button in ARS' Control Panel page to upload and delete files in your S3 bucket, or create new directories into it. When you are creating items in this category, make sure you select "File" from the type drop-down and after a few seconds you will see a list of your files stored on Amazon S3. And, yes, Amazon S3 integration works with Normal and Bleeding Edge categories alike!