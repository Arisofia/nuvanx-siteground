with open("wp-content/themes/nuvanx-medical/inc/nvx-content-presentation.php", "r") as f:
    lines = f.readlines()

new_lines = []
skip = False
for line in lines:
    if "/**" in line and "Safely executes a preg_replace/preg_replace_callback" in "".join(lines[lines.index(line):lines.index(line)+3]):
        skip = True
    
    if not skip:
        new_lines.append(line)
        
    if skip and line.strip() == "}":
        # Check if the previous lines were the function nvx_content_preg_replace_keep
        skip = False

# Better: just use regex
import re
with open("wp-content/themes/nuvanx-medical/inc/nvx-content-presentation.php", "r") as f:
    content = f.read()

content = re.sub(r'/\*\*\s+\*\s+Safely executes a preg_replace.*?\n\}\n', '', content, flags=re.DOTALL)

with open("wp-content/themes/nuvanx-medical/inc/nvx-content-presentation.php", "w") as f:
    f.write(content)
