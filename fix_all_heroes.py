import os
import re

directory = 'wp-content/themes/nuvanx-medical/inc'

replacements = {
    'nvx-brand-hero__copy': 'nvx-editorial-hero__copy',
    'nvx-brand-hero__title': 'nvx-heading',
    'nvx-brand-hero__lead': 'nvx-lead',
    'nvx-brand-hero__description': 'nvx-lead',
    'nvx-brand-kicker': 'nvx-eyebrow'
}

files_changed = 0

for root, dirs, files in os.walk(directory):
    for filename in files:
        if filename.endswith('.php'):
            filepath = os.path.join(root, filename)
            with open(filepath, 'r') as f:
                content = f.read()

            new_content = content
            for old_class, new_class in replacements.items():
                new_content = new_content.replace(old_class, new_class)

            if new_content != content:
                with open(filepath, 'w') as f:
                    f.write(new_content)
                print(f"Updated {filepath}")
                files_changed += 1

print(f"Total files updated: {files_changed}")
