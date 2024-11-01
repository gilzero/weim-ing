Description:
IP2Location is a non-intrusive geo IP solution to help you to identify
visitor's geographical location, i.e. country, region, city, district, latitude,
longitude, ZIP code, time zone, connection speed, ISP and domain name, IDD
country code, area code, weather station code and name, and mobile carrier,
elevation, usage type, address type, IAB category, and ASN information using a
proprietary IP address lookup database and technology without invading the
Internet user's privacy.

This module enables geolocation data in response headers in real time. You
also can use the ip2location_get_records function to get geolocation
information from other modules or themes. IP2Location module comes with
empty database. Please download a free database from
http://lite.ip2location.com or commercial version from
http://www.ip2location.com.


Requirements:
Drupal 10.x | 11.x


Installation:
1. Unzip the package to /modules directory.
2. Run the command "composer require ip2location/ip2location-php" on command line in Drupal installation folder.
3. Go to Drupal Administrator → Extend.
4. Locate "IP2Location" from the list and enable it and save.
5. Go to Administrator → Configuration.
6. Locate "IP2Location Settings", and go into settings page.
7. Insert the absolute path for IP2Location BIN database and save the configuration.


Usage:
To use within Drupal from other modules or themes, use the function
"ip2location_get_records()" to get geolocation information. If will return
the result in object or null if result not available.

Fields:
IP_ADDRESS: Visitor IP address.
COUNTRY_CODE: Two-character country code based on ISO 3166.
COUNTRY_NAME: Country name based on ISO 3166.
REGION_NAME: Region or state name.
CITY_NAME: City name.
LATITUDE: Latitude of city.
LONGITUDE: Longitude of city.
ISP: Internet Service Provider or company's name.
DOMAIN_NAME: Internet domain name associated to IP address range.
ZIP_CODE: ZIP/Postal code.
TIME_ZONE: UTC time zone.
NET_SPEED: Internet connection type.
IDD_CODE: The IDD prefix to call the city from another country.
AREA_CODE: A varying length number assigned to geographic areas for call
           between cities.
WEATHER_STATION_CODE: The special code to identify the nearest weather
                      observation station.
WEATHER_STATION_NAME: The name of the nearest weather observation station.
MCC: Mobile Country Codes (MCC) as defined in ITU E.212 for use in
     identifying mobile stations in wireless telephone networks, particularly
     GSM and UMTS networks.
MNC: Mobile Network Code (MNC) is used in combination with a Mobile Country
     Code (MCC) to uniquely identify a mobile phone operator or carrier.
CARRIER_NAME: Commercial brand associated with the mobile carrier.
ELEVATION: Average height of city above sea level in meters (m).
USAGE_TYPE: Usage type classification of ISP or company
ADDRESS_TYPE: IP address types as defined in Internet Protocol version 4 (IPv4)
              and Internet Protocol version 6 (IPv6).
CATEGORY: The domain category is based on IAB Tech Lab Content Taxonomy. These
          categories are comprised of Tier-1 and Tier-2 (if available) level
          categories widely used in services like advertising, Internet
          security and filtering appliances.
DISTRICT: District or county name.
ASN: Autonomous system number (ASN).
AS: Autonomous system (AS) name.

Support:
Please use the issue queue for filing bugs with this module at
http://drupal.org/project/issues/ip2location
