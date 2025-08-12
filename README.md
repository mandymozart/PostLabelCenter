<!-- PROJECT LOGO -->
<br />
<div align="center">
  <a href="https://www.post.at">
    <img src="src/Resources/config/plugin.png" alt="Logo" width="80" height="80">
  </a>
</div>

# Post Label Center 2.0.8

Dieses Plugin dient als Schnittstelle für Shopware-Shops zum Post Label Center für Geschäftskunden.
Enthalten sind Funktionen wie automatisches Erstellen der Labels, als auch manuelles Erstellen inkl. Möglichkeit der
Erstellung von Zolldokumenten

### Mindestvoraussetzungen

Shopware: Version 6.6

## Installation

Installation des Plugins erfolgt über die Shopware-Administration.

```Erweiterungen - Meine Erweiterungen - Plugin-Upload```

Danach muss das Plugin noch installiert und aktiviert werden.

## Einstellungen

Unter den Plugin-Einstellungen müssen die Daten des Kunden im jeweiligen Verkaufskanal hinterlegt werden.
Bitte beachten, dass bereits einige Default-Einstellungen im Plugin hinterlegt sind und diese sich auf ihre
Labelgenerierung auswirken können.

## Changelog

- 2.0.14
    - patch strict types for shopware 6.7
- 2.0.8
    - Bulk-Modal Handling Optimizations, other bugfixes
- 2.0.7
    - Redesign of Bulk-Modal Handling, bugfixes
- 2.0.6
    - corrected config for automatic statuslabel, small bugfixes and code optimizations, bugfixes Bulk labels
- 2.0.5
    - removed duplicate pluginconfig, bugfix for manual label, bugfix in address creation
- 2.0.4
    - added new Plugin-Config to disable automatic label creation on shipping status change
- 2.0.3
    - Added Shopversion Metatag
- 2.0.2
    - Bugfixes manual shipment labels & inline-edit elements
- 2.0.1
    - Bugfixes for label creation
- 2.0.0
    - Initial Release for Shopware 6.6
- 1.0.0
    - Release 
