with open("wp-content/themes/nuvanx-medical/inc/nvx-structured-data.php", "r") as f:
    content = f.read()

content = content.replace("function nvx_endolift_price_from_eur() { return 0.0; }\n\n\treturn (float) $catalog['endolift']['ojeras']['pvp'];\n}", "function nvx_endolift_price_from_eur() { return 0.0; }")

with open("wp-content/themes/nuvanx-medical/inc/nvx-structured-data.php", "w") as f:
    f.write(content)
